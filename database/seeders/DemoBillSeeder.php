<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoBillSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('email', 'demo@srimuruganstores.com')->firstOrFail();
        $cashier = User::where('tenant_id', $tenant->id)->where('role', 'cashier')->firstOrFail();
        $products = Product::where('tenant_id', $tenant->id)->get();
        $customers = Customer::where('tenant_id', $tenant->id)->get();
        $paymentMethods = ['cash', 'cash', 'cash', 'upi', 'upi', 'card', 'credit'];

        for ($i = 0; $i < 30; $i++) {
            $billedAt = Carbon::now()->subDays($i % 30)->setTime(rand(9, 20), rand(0, 59));
            $billProducts = $products->random(rand(2, 5));
            $customer = $i % 5 === 0 ? $customers->random() : null;
            $paymentMethod = fake()->randomElement($paymentMethods);
            $billType = $paymentMethod === 'credit' ? 'credit' : 'retail';

            $subtotal = 0;
            $cgst = 0;
            $sgst = 0;
            $itemsData = [];

            foreach ($billProducts as $product) {
                $qty = rand(1, 4);
                $price = (float) $product->selling_price;
                $gstRate = (float) $product->gst_rate;
                $lineSubtotal = round($price * $qty, 2);
                $gstAmount = round($lineSubtotal * $gstRate / 100, 2);
                $lineTotal = $lineSubtotal + $gstAmount;

                $subtotal += $lineSubtotal;
                $cgst += round($gstAmount / 2, 2);
                $sgst += round($gstAmount / 2, 2);

                $itemsData[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'price' => $price,
                    'gst_amount' => $gstAmount,
                    'total' => $lineTotal,
                ];
            }

            $total = round($subtotal + $cgst + $sgst, 2);
            $paidAmount = ($billType === 'credit' && rand(0, 1)) ? 0 : $total;
            $dueAmount = $total - $paidAmount;
            $paymentStatus = $dueAmount <= 0 ? 'paid' : 'due';

            $bill = Bill::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer?->id,
                'user_id' => $cashier->id,
                'bill_type' => $billType,
                'subtotal' => round($subtotal, 2),
                'discount_percent' => 0,
                'discount_amount' => 0,
                'extra_charges' => 0,
                'cgst_amount' => round($cgst, 2),
                'sgst_amount' => round($sgst, 2),
                'igst_amount' => 0,
                'total_amount' => $total,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'billed_at' => $billedAt,
            ]);

            foreach ($itemsData as $item) {
                BillItem::create([
                    'bill_id' => $bill->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'unit_price' => $item['price'],
                    'purchase_price' => (float) $item['product']->purchase_price,
                    'quantity' => $item['qty'],
                    'discount_percent' => 0,
                    'gst_rate' => (float) $item['product']->gst_rate,
                    'gst_amount' => $item['gst_amount'],
                    'total' => $item['total'],
                ]);

                if ($item['product']->stock_quantity >= $item['qty']) {
                    $before = $item['product']->stock_quantity;
                    $item['product']->decrement('stock_quantity', $item['qty']);
                    $item['product']->refresh();

                    StockMovement::create([
                        'tenant_id' => $tenant->id,
                        'product_id' => $item['product']->id,
                        'user_id' => $cashier->id,
                        'bill_id' => $bill->id,
                        'type' => 'sale',
                        'quantity' => -$item['qty'],
                        'stock_before' => $before,
                        'stock_after' => $item['product']->stock_quantity,
                    ]);
                }
            }

            if ($billType === 'credit' && $customer && $dueAmount > 0) {
                $customer->increment('credit_balance', $dueAmount);
            }
        }
    }
}
