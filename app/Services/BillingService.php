<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\BillRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingService
{
    public function __construct(private readonly BillRepository $billRepository) {}

    public function calculateTotals(
        array $items,
        float $discountPct,
        float $extraCharges,
        string $billType
    ): array {
        $subtotal = 0;
        $totalCgst = 0;
        $totalSgst = 0;
        $processedItems = [];

        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $unitPrice = $product->effectivePriceForType($billType);
            $qty = (int) $item['quantity'];
            $itemDiscount = (float) ($item['discount_percent'] ?? 0);

            $lineBeforeDiscount = $unitPrice * $qty;
            $lineAfterDiscount = $lineBeforeDiscount * (1 - $itemDiscount / 100);

            if ($product->gst_type === 'exclusive') {
                $gstAmount = $lineAfterDiscount * ((float) $product->gst_rate / 100);
                $cgst = $gstAmount / 2;
                $sgst = $gstAmount / 2;
            } else {
                // Inclusive: back-calculate
                $gstRate = (float) $product->gst_rate;
                $taxableValue = $lineAfterDiscount / (1 + $gstRate / 100);
                $gstAmount = $lineAfterDiscount - $taxableValue;
                $cgst = $gstAmount / 2;
                $sgst = $gstAmount / 2;
                $lineAfterDiscount = $taxableValue; // subtotal shows pre-tax for inclusive
            }

            $subtotal += $lineAfterDiscount;
            $totalCgst += $cgst;
            $totalSgst += $sgst;

            $processedItems[] = array_merge($item, [
                'unit_price' => round($unitPrice, 2),
                'gst_amount' => round($gstAmount, 2),
                'total' => round($lineAfterDiscount + $cgst + $sgst, 2),
            ]);
        }

        $discountAmount = round($subtotal * ($discountPct / 100), 2);
        $taxableAmount = $subtotal - $discountAmount;

        // Recalculate GST after bill-level discount
        $gstRatio = $subtotal > 0 ? $taxableAmount / $subtotal : 1;
        $totalCgst = round($totalCgst * $gstRatio, 2);
        $totalSgst = round($totalSgst * $gstRatio, 2);

        $totalAmount = round($taxableAmount + $totalCgst + $totalSgst + $extraCharges, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => $discountAmount,
            'cgst_amount' => $totalCgst,
            'sgst_amount' => $totalSgst,
            'total_amount' => $totalAmount,
            'items' => $processedItems,
        ];
    }

    public function createBill(Tenant $tenant, User $cashier, array $data): Bill
    {
        // Validate stock availability
        foreach ($data['items'] as $item) {
            $product = Product::where('tenant_id', $tenant->id)
                ->where('id', $item['product_id'])
                ->where('is_active', true)
                ->firstOrFail();

            if ($product->stock_quantity < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => "Insufficient stock for '{$product->name}'. Available: {$product->stock_quantity}",
                ]);
            }
        }

        $totals = $this->calculateTotals(
            $data['items'],
            (float) ($data['discount_percent'] ?? 0),
            (float) ($data['extra_charges'] ?? 0),
            $data['bill_type']
        );

        $paidAmount = (float) ($data['paid_amount'] ?? $totals['total_amount']);
        $dueAmount = max(0, $totals['total_amount'] - $paidAmount);
        $paymentStatus = $dueAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'due');

        return $this->billRepository->createWithItems($tenant, $cashier, array_merge($data, $totals, [
            'paid_amount' => $paidAmount,
            'due_amount' => $dueAmount,
            'payment_status' => $paymentStatus,
        ]));
    }

    public function generateReceipt(Bill $bill): array
    {
        $bill->load(['tenant', 'tenant.plan', 'customer', 'user', 'items']);

        return [
            'bill_number' => $bill->bill_number,
            'billed_at' => $bill->billed_at->format('d M Y, h:i A'),
            'shop' => [
                'name' => $bill->tenant->shop_name,
                'address' => $bill->tenant->address,
                'city' => $bill->tenant->city,
                'state' => $bill->tenant->state,
                'phone' => $bill->tenant->phone,
                'gstin' => $bill->tenant->gstin,
                'logo_url' => $bill->tenant->logo_url,
                'receipt_footer' => $bill->tenant->receipt_footer,
            ],
            'customer' => $bill->customer ? [
                'name' => $bill->customer->name,
                'phone' => $bill->customer->phone,
            ] : null,
            'cashier' => $bill->user->name,
            'items' => $bill->items->map(fn (BillItem $i) => [
                'product_name' => $i->product_name,
                'quantity' => $i->quantity,
                'unit_price' => number_format((float) $i->unit_price, 2),
                'gst_rate' => $i->gst_rate,
                'gst_amount' => number_format((float) $i->gst_amount, 2),
                'total' => number_format((float) $i->total, 2),
            ])->toArray(),
            'subtotal' => number_format((float) $bill->subtotal, 2),
            'discount_percent' => $bill->discount_percent,
            'discount_amount' => number_format((float) $bill->discount_amount, 2),
            'cgst_amount' => number_format((float) $bill->cgst_amount, 2),
            'sgst_amount' => number_format((float) $bill->sgst_amount, 2),
            'total_amount' => number_format((float) $bill->total_amount, 2),
            'paid_amount' => number_format((float) $bill->paid_amount, 2),
            'due_amount' => number_format((float) $bill->due_amount, 2),
            'payment_method' => $bill->payment_method,
            'payment_status' => $bill->payment_status,
        ];
    }

    public function processReturn(Bill $originalBill, array $returnItems): Bill
    {
        return DB::transaction(function () use ($originalBill, $returnItems) {
            $returnBillData = [
                'customer_id' => $originalBill->customer_id,
                'bill_type' => $originalBill->bill_type,
                'payment_method' => $originalBill->payment_method,
                'discount_percent' => 0,
                'extra_charges' => 0,
                'notes' => "Return of bill #{$originalBill->bill_number}",
                'items' => [],
            ];

            $subtotal = 0;
            foreach ($returnItems as $returnItem) {
                $originalItem = $originalBill->items()
                    ->where('product_id', $returnItem['product_id'])
                    ->firstOrFail();

                $qty = (int) $returnItem['quantity'];
                $product = Product::lockForUpdate()->findOrFail($returnItem['product_id']);
                $stockBefore = $product->stock_quantity;
                $product->increment('stock_quantity', $qty);

                StockMovement::create([
                    'tenant_id' => $originalBill->tenant_id,
                    'product_id' => $product->id,
                    'user_id' => $originalBill->user_id,
                    'bill_id' => $originalBill->id,
                    'type' => 'return',
                    'quantity' => $qty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBefore + $qty,
                ]);

                $lineTotal = round((float) $originalItem->unit_price * $qty, 2);
                $subtotal += $lineTotal;

                $returnBillData['items'][] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $originalItem->unit_price,
                    'discount_percent' => 0,
                    'gst_amount' => 0,
                    'total' => $lineTotal,
                ];
            }

            $creditNote = Bill::create([
                'tenant_id' => $originalBill->tenant_id,
                'customer_id' => $originalBill->customer_id,
                'user_id' => $originalBill->user_id,
                'bill_type' => $originalBill->bill_type,
                'subtotal' => $subtotal,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'extra_charges' => 0,
                'cgst_amount' => 0,
                'sgst_amount' => 0,
                'igst_amount' => 0,
                'total_amount' => -$subtotal,
                'paid_amount' => -$subtotal,
                'due_amount' => 0,
                'payment_status' => 'paid',
                'payment_method' => $originalBill->payment_method,
                'notes' => "Credit note for bill #{$originalBill->bill_number}",
                'billed_at' => now(),
            ]);

            foreach ($returnBillData['items'] as $item) {
                BillItem::create(array_merge($item, ['bill_id' => $creditNote->id, 'product_name' => Product::find($item['product_id'])->name, 'purchase_price' => 0]));
            }

            if ($originalBill->bill_type === 'credit' && $originalBill->customer_id) {
                \App\Models\Customer::where('id', $originalBill->customer_id)
                    ->decrement('credit_balance', $subtotal);
            }

            return $creditNote->load(['items', 'customer', 'user']);
        });
    }
}
