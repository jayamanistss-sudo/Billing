<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StssSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('email', 'jayamanistss@gmail.com')->firstOrFail();
        $owner  = User::where('tenant_id', $tenant->id)->where('role', 'owner')->firstOrFail();

        // ── Categories ────────────────────────────────────────────────────────
        $catNames = ['Electronics', 'Mobile Accessories', 'Computer Parts', 'Cables & Adapters', 'Power & Charging'];
        $cats = [];
        foreach ($catNames as $name) {
            $cats[$name] = Category::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $name],
                ['tenant_id' => $tenant->id, 'is_active' => true]
            )->id;
        }

        // ── Products ──────────────────────────────────────────────────────────
        // [name, category, sku, purchase_price, selling_price, wholesale_price, gst_rate, stock, low_stock]
        $products = [
            // Electronics
            ['JBL Go 3 Speaker', 'Electronics', 'STSS-001', 950, 1299, 1150, 18, 25, 5],
            ['boAt Rockerz 255 Earphones', 'Electronics', 'STSS-002', 680, 899, 799, 18, 30, 6],
            ['MI True Wireless Earbuds', 'Electronics', 'STSS-003', 1100, 1499, 1349, 18, 20, 4],
            ['Portronics Power Bank 10000mAh', 'Electronics', 'STSS-004', 750, 999, 899, 18, 18, 4],
            ['HP Mouse M10 Wired', 'Electronics', 'STSS-005', 220, 349, 299, 18, 40, 8],
            // Mobile Accessories
            ['Tempered Glass (Universal)', 'Mobile Accessories', 'STSS-006', 35, 79, 59, 18, 100, 20],
            ['Phone Back Cover Silicon', 'Mobile Accessories', 'STSS-007', 45, 99, 79, 18, 80, 15],
            ['Ring Holder Stand', 'Mobile Accessories', 'STSS-008', 30, 69, 55, 18, 60, 10],
            ['Car Mount Dashboard', 'Mobile Accessories', 'STSS-009', 120, 249, 199, 18, 35, 6],
            ['PopSocket Grip', 'Mobile Accessories', 'STSS-010', 25, 59, 45, 18, 90, 20],
            // Computer Parts
            ['Kingston 8GB DDR4 RAM', 'Computer Parts', 'STSS-011', 1450, 1899, 1699, 18, 15, 3],
            ['WD 1TB HDD', 'Computer Parts', 'STSS-012', 2800, 3499, 3199, 18, 12, 3],
            ['Keyboard USB Wired', 'Computer Parts', 'STSS-013', 280, 449, 379, 18, 28, 5],
            ['USB Hub 4-Port', 'Computer Parts', 'STSS-014', 180, 299, 249, 18, 35, 8],
            ['Webcam 720p', 'Computer Parts', 'STSS-015', 650, 899, 799, 18, 16, 4],
            // Cables & Adapters
            ['Type-C Cable 1m Braided', 'Cables & Adapters', 'STSS-016', 55, 129, 99, 18, 120, 25],
            ['Lightning Cable 1m', 'Cables & Adapters', 'STSS-017', 60, 139, 109, 18, 100, 20],
            ['HDMI Cable 1.5m', 'Cables & Adapters', 'STSS-018', 90, 199, 169, 18, 50, 10],
            ['OTG Adapter Type-C', 'Cables & Adapters', 'STSS-019', 28, 69, 49, 18, 80, 15],
            ['AUX Cable 1m', 'Cables & Adapters', 'STSS-020', 30, 79, 59, 18, 70, 12],
            // Power & Charging
            ['65W GaN Charger', 'Power & Charging', 'STSS-021', 480, 699, 599, 18, 22, 5],
            ['20W Fast Charger', 'Power & Charging', 'STSS-022', 220, 349, 299, 18, 35, 7],
            ['Wireless Charger 15W', 'Power & Charging', 'STSS-023', 350, 549, 449, 18, 20, 4],
            ['Multi-Port USB Charger 4A', 'Power & Charging', 'STSS-024', 260, 399, 349, 18, 30, 6],
            ['Extension Board 4 Socket', 'Power & Charging', 'STSS-025', 320, 499, 429, 18, 25, 5],
        ];

        $productModels = [];
        foreach ($products as $p) {
            $productModels[] = Product::updateOrCreate(
                ['tenant_id' => $tenant->id, 'sku' => $p[2]],
                [
                    'tenant_id'           => $tenant->id,
                    'category_id'         => $cats[$p[1]],
                    'name'                => $p[0],
                    'sku'                 => $p[2],
                    'purchase_price'      => $p[3],
                    'selling_price'       => $p[4],
                    'wholesale_price'     => $p[5],
                    'gst_rate'            => $p[6],
                    'gst_type'            => 'exclusive',
                    'stock_quantity'      => $p[7],
                    'reorder_level'       => $p[8],
                    'unit'                => 'piece',
                    'is_active'           => true,
                ]
            );
        }

        // ── Customers ─────────────────────────────────────────────────────────
        $customersData = [
            ['Arun Electronics', '9841012345', 'arun@arunelectronics.com', '12, Anna Salai, Chennai'],
            ['Selva Mobile World', '9841023456', null, '45, GST Road, Tambaram'],
            ['Karthik Computers', '9500012345', 'karthik@kkcomputers.in', '8/2, Mount Road, Chennai'],
            ['Priya Accessories', '9841034567', null, '23, Velachery Main Road'],
            ['Tech Zone', '9944012345', 'techzone@gmail.com', '67, Anna Nagar, Chennai'],
        ];

        $customerModels = [];
        foreach ($customersData as $c) {
            $customerModels[] = Customer::updateOrCreate(
                ['tenant_id' => $tenant->id, 'phone' => $c[1]],
                [
                    'tenant_id' => $tenant->id,
                    'name'      => $c[0],
                    'phone'     => $c[1],
                    'email'     => $c[2],
                    'address'   => $c[3],
                ]
            );
        }

        // ── Bills — 45 bills spread across the last 30 days ──────────────────
        $productCollection = collect($productModels);
        $customerCollection = collect($customerModels);
        $paymentMethods = ['cash', 'cash', 'upi', 'upi', 'card', 'credit'];

        for ($i = 0; $i < 45; $i++) {
            $billedAt = Carbon::now()->subDays($i % 30)->setTime(rand(9, 20), rand(0, 59));
            $billProducts = $productCollection->random(rand(1, 4));
            $paymentMethod = fake()->randomElement($paymentMethods);
            $billType = $paymentMethod === 'credit' ? 'credit' : ($i % 8 === 0 ? 'wholesale' : 'retail');
            $customer = ($billType === 'credit' || $i % 4 === 0) ? $customerCollection->random() : null;
            $discountPercent = ($i % 7 === 0) ? 5 : 0;

            $subtotal = 0;
            $cgst = 0;
            $sgst = 0;
            $itemsData = [];

            foreach ($billProducts as $product) {
                $qty = rand(1, 5);
                $price = (float) ($billType === 'wholesale' && $product->wholesale_price ? $product->wholesale_price : $product->selling_price);
                $gstRate = (float) $product->gst_rate;
                $lineSubtotal = round($price * $qty, 2);
                $gstAmount = round($lineSubtotal * $gstRate / 100, 2);
                $lineTotal = round($lineSubtotal + $gstAmount, 2);

                $subtotal += $lineSubtotal;
                $cgst += round($gstAmount / 2, 2);
                $sgst += round($gstAmount / 2, 2);

                $itemsData[] = [
                    'product'    => $product,
                    'qty'        => $qty,
                    'price'      => $price,
                    'gst_amount' => $gstAmount,
                    'total'      => $lineTotal,
                ];
            }

            $discountAmount = round($subtotal * $discountPercent / 100, 2);
            $total = round($subtotal - $discountAmount + $cgst + $sgst, 2);
            $paidAmount = ($billType === 'credit' && rand(0, 1)) ? 0 : $total;
            $dueAmount = round($total - $paidAmount, 2);
            $paymentStatus = $dueAmount <= 0 ? 'paid' : 'due';

            $bill = Bill::create([
                'tenant_id'       => $tenant->id,
                'customer_id'     => $customer?->id,
                'user_id'         => $owner->id,
                'bill_type'       => $billType,
                'subtotal'        => round($subtotal, 2),
                'discount_percent'=> $discountPercent,
                'discount_amount' => $discountAmount,
                'extra_charges'   => 0,
                'cgst_amount'     => round($cgst, 2),
                'sgst_amount'     => round($sgst, 2),
                'igst_amount'     => 0,
                'total_amount'    => $total,
                'paid_amount'     => $paidAmount,
                'due_amount'      => $dueAmount,
                'payment_status'  => $paymentStatus,
                'payment_method'  => $paymentMethod,
                'billed_at'       => $billedAt,
            ]);

            foreach ($itemsData as $item) {
                BillItem::create([
                    'bill_id'          => $bill->id,
                    'product_id'       => $item['product']->id,
                    'product_name'     => $item['product']->name,
                    'unit_price'       => $item['price'],
                    'purchase_price'   => (float) $item['product']->purchase_price,
                    'quantity'         => $item['qty'],
                    'discount_percent' => 0,
                    'gst_rate'         => (float) $item['product']->gst_rate,
                    'gst_amount'       => $item['gst_amount'],
                    'total'            => $item['total'],
                ]);

                if ($item['product']->stock_quantity >= $item['qty']) {
                    $before = $item['product']->stock_quantity;
                    $item['product']->decrement('stock_quantity', $item['qty']);
                    $item['product']->refresh();

                    StockMovement::create([
                        'tenant_id'   => $tenant->id,
                        'product_id'  => $item['product']->id,
                        'user_id'     => $owner->id,
                        'bill_id'     => $bill->id,
                        'type'        => 'sale',
                        'quantity'    => -$item['qty'],
                        'stock_before'=> $before,
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
