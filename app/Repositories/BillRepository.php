<?php

namespace App\Repositories;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BillRepository extends BaseRepository
{
    public function __construct(Bill $model)
    {
        parent::__construct($model);
    }

    public function createWithItems(Tenant $tenant, User $cashier, array $data): Bill
    {
        return DB::transaction(function () use ($tenant, $cashier, $data) {
            $bill = Bill::create([
                'tenant_id' => $tenant->id,
                'user_id' => $cashier->id,
                'customer_id' => $data['customer_id'] ?? null,
                'bill_type' => $data['bill_type'],
                'subtotal' => $data['subtotal'],
                'discount_percent' => $data['discount_percent'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'extra_charges' => $data['extra_charges'] ?? 0,
                'extra_charges_label' => $data['extra_charges_label'] ?? null,
                'cgst_amount' => $data['cgst_amount'] ?? 0,
                'sgst_amount' => $data['sgst_amount'] ?? 0,
                'igst_amount' => $data['igst_amount'] ?? 0,
                'total_amount' => $data['total_amount'],
                'paid_amount' => $data['paid_amount'] ?? $data['total_amount'],
                'due_amount' => $data['due_amount'] ?? 0,
                'payment_status' => $data['payment_status'],
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
                'billed_at' => now(),
            ]);

            foreach ($data['items'] as $itemData) {
                $product = Product::lockForUpdate()->findOrFail($itemData['product_id']);

                BillItem::create([
                    'bill_id' => $bill->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $itemData['unit_price'],
                    'purchase_price' => $product->purchase_price,
                    'quantity' => $itemData['quantity'],
                    'discount_percent' => $itemData['discount_percent'] ?? 0,
                    'gst_rate' => $product->gst_rate,
                    'gst_amount' => $itemData['gst_amount'] ?? 0,
                    'total' => $itemData['total'],
                ]);

                $stockBefore = $product->stock_quantity;
                $product->decrement('stock_quantity', $itemData['quantity']);
                $product->refresh();

                StockMovement::create([
                    'tenant_id' => $tenant->id,
                    'product_id' => $product->id,
                    'user_id' => $cashier->id,
                    'bill_id' => $bill->id,
                    'type' => 'sale',
                    'quantity' => -$itemData['quantity'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $product->stock_quantity,
                    'unit_cost' => $itemData['unit_price'],
                ]);
            }

            if ($data['bill_type'] === 'credit' && !empty($data['customer_id'])) {
                \App\Models\Customer::where('id', $data['customer_id'])
                    ->increment('credit_balance', $data['due_amount'] ?? $data['total_amount']);
            }

            return $bill->load(['items', 'customer', 'user']);
        });
    }

    public function dailySummary(Tenant $tenant, Carbon $date): array
    {
        $bills = Bill::where('tenant_id', $tenant->id)
            ->whereDate('billed_at', $date)
            ->whereNull('deleted_at')
            ->get();

        return [
            'total_sales' => $bills->sum('total_amount'),
            'total_bills' => $bills->count(),
            'cash_sales' => $bills->where('payment_method', 'cash')->sum('total_amount'),
            'upi_sales' => $bills->where('payment_method', 'upi')->sum('total_amount'),
            'card_sales' => $bills->where('payment_method', 'card')->sum('total_amount'),
            'credit_sales' => $bills->where('payment_method', 'credit')->sum('total_amount'),
            'avg_bill_value' => $bills->count() ? round($bills->sum('total_amount') / $bills->count(), 2) : 0,
        ];
    }

    public function monthlySummary(Tenant $tenant, int $year, int $month): array
    {
        return Bill::where('tenant_id', $tenant->id)
            ->whereYear('billed_at', $year)
            ->whereMonth('billed_at', $month)
            ->whereNull('deleted_at')
            ->selectRaw('
                SUM(total_amount) as total_sales,
                COUNT(*) as total_bills,
                SUM(cgst_amount) as total_cgst,
                SUM(sgst_amount) as total_sgst,
                SUM(discount_amount) as total_discount,
                AVG(total_amount) as avg_bill_value
            ')
            ->first()
            ->toArray();
    }
}
