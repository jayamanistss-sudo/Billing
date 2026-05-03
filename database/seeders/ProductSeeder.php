<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('email', 'demo@srimuruganstores.com')->firstOrFail();
        $cats = Category::where('tenant_id', $tenant->id)->pluck('id', 'name');

        $products = [
            // Grocery
            ['Tata Salt 1kg', $cats['Grocery'], 'SKU001', '8901058000784', 'kg', 22, 26, null, 5, 'exclusive', 80, 10],
            ['Aashirvaad Atta 5kg', $cats['Grocery'], 'SKU002', '8901058001330', 'pack', 165, 185, null, 0, 'exclusive', 40, 5],
            ['Tata Tea Gold 250g', $cats['Grocery'], 'SKU003', '8901058004454', 'pack', 95, 115, null, 5, 'exclusive', 60, 10],
            ['Bru Coffee 100g', $cats['Grocery'], 'SKU004', '8901058007776', 'pack', 82, 95, null, 5, 'exclusive', 35, 5],
            ['Sunflower Oil 1L', $cats['Grocery'], 'SKU005', '8901058002010', 'litre', 130, 148, null, 5, 'exclusive', 50, 8],
            ['Basmati Rice 5kg', $cats['Grocery'], 'SKU006', '8901058003124', 'pack', 320, 370, 340, 5, 'exclusive', 30, 5],
            ['Toor Dal 1kg', $cats['Grocery'], 'SKU007', '8901058003131', 'kg', 105, 122, null, 5, 'exclusive', 45, 8],
            // Dairy
            ['Amul Butter 500g', $cats['Dairy'], 'SKU008', '8901058001347', 'pack', 230, 260, null, 12, 'exclusive', 20, 5],
            ['Amul Full Cream Milk 1L', $cats['Dairy'], 'SKU009', '8901058005530', 'litre', 58, 65, null, 0, 'exclusive', 100, 20],
            ['Britannia Cheese 200g', $cats['Dairy'], 'SKU010', '8901058007820', 'pack', 95, 110, null, 12, 'exclusive', 15, 5],
            // Snacks
            ['Maggi Noodles 70g', $cats['Snacks'], 'SKU011', '8901058007837', 'piece', 12, 15, null, 0, 'exclusive', 120, 20],
            ['Parle-G Biscuits 800g', $cats['Snacks'], 'SKU012', '8901058007844', 'pack', 42, 50, null, 0, 'exclusive', 60, 10],
            ['Lay\'s Classic Salted 100g', $cats['Snacks'], 'SKU013', '8901058007851', 'pack', 18, 22, null, 12, 'exclusive', 80, 15],
            ['Good Day Cashew Biscuits 100g', $cats['Snacks'], 'SKU014', '8901058007868', 'pack', 27, 32, null, 12, 'exclusive', 70, 10],
            ['KitKat 4 Finger 41.5g', $cats['Snacks'], 'SKU015', '8901058007875', 'piece', 55, 65, null, 18, 'exclusive', 40, 10],
            // Drinks
            ['Coca-Cola 600ml', $cats['Drinks'], 'SKU016', '8901058007882', 'ml', 35, 42, null, 28, 'exclusive', 100, 20],
            ['Thums Up 750ml', $cats['Drinks'], 'SKU017', '8901058007899', 'ml', 38, 45, null, 28, 'exclusive', 80, 15],
            ['Frooti 200ml', $cats['Drinks'], 'SKU018', '8901058007905', 'ml', 15, 18, null, 12, 'exclusive', 150, 30],
            ['Bisleri Water 1L', $cats['Drinks'], 'SKU019', '8901058007912', 'litre', 18, 22, null, 0, 'exclusive', 200, 50],
            ['Red Bull 250ml', $cats['Drinks'], 'SKU020', '8901058007929', 'ml', 95, 115, null, 28, 'exclusive', 30, 5],
            // Household
            ['Harpic Toilet Cleaner 1L', $cats['Household'], 'SKU021', '8901058007936', 'litre', 115, 135, null, 18, 'exclusive', 25, 5],
            ['Surf Excel 1kg', $cats['Household'], 'SKU022', '8901058007943', 'kg', 155, 180, null, 18, 'exclusive', 35, 5],
            ['Vim Dishwash Bar 200g', $cats['Household'], 'SKU023', '8901058007950', 'piece', 22, 28, null, 18, 'exclusive', 60, 10],
            ['Phenyl 500ml', $cats['Household'], 'SKU024', '8901058007967', 'ml', 38, 48, null, 18, 'exclusive', 30, 5],
            ['Scotch-Brite Scrub 3M', $cats['Household'], 'SKU025', '8901058007974', 'piece', 28, 35, null, 18, 'exclusive', 45, 8],
            // Personal Care
            ['Colgate Toothpaste 200g', $cats['Personal Care'], 'SKU026', '8901058007981', 'piece', 75, 92, null, 12, 'exclusive', 50, 10],
            ['Lux Soap 100g', $cats['Personal Care'], 'SKU027', '8901058007998', 'piece', 45, 55, null, 12, 'exclusive', 80, 15],
            ['Dove Shampoo 340ml', $cats['Personal Care'], 'SKU028', '8901058008001', 'ml', 195, 235, null, 18, 'exclusive', 20, 5],
            ['Dettol Handwash 250ml', $cats['Personal Care'], 'SKU029', '8901058008018', 'ml', 88, 105, null, 18, 'exclusive', 30, 5],
            ['Parachute Coconut Oil 200ml', $cats['Personal Care'], 'SKU030', '8901058008025', 'ml', 78, 92, null, 18, 'exclusive', 40, 8],
            // More Grocery
            ['MDH Garam Masala 100g', $cats['Grocery'], 'SKU031', null, 'pack', 55, 68, null, 5, 'exclusive', 40, 8],
            ['Everest Turmeric 200g', $cats['Grocery'], 'SKU032', null, 'pack', 35, 42, null, 5, 'exclusive', 50, 10],
            ['Shan Biryani Mix', $cats['Grocery'], 'SKU033', null, 'pack', 45, 55, null, 5, 'exclusive', 30, 5],
            ['Chilly Powder 500g', $cats['Grocery'], 'SKU034', null, 'kg', 65, 78, null, 5, 'exclusive', 35, 5],
            ['Coriander Powder 200g', $cats['Grocery'], 'SKU035', null, 'pack', 28, 35, null, 5, 'exclusive', 45, 8],
            // More Dairy
            ['Amul Lassi 200ml', $cats['Dairy'], 'SKU036', null, 'ml', 20, 25, null, 12, 'exclusive', 60, 15],
            ['Nandini Ghee 500ml', $cats['Dairy'], 'SKU037', null, 'ml', 265, 310, null, 5, 'exclusive', 20, 5],
            // More Snacks
            ['Kurkure Masala Munch 90g', $cats['Snacks'], 'SKU038', null, 'pack', 18, 22, null, 12, 'exclusive', 90, 15],
            ['Haldiram Bhujia 400g', $cats['Snacks'], 'SKU039', null, 'pack', 85, 100, null, 12, 'exclusive', 40, 8],
            ['Diary Milk Silk 60g', $cats['Snacks'], 'SKU040', null, 'piece', 85, 100, null, 18, 'exclusive', 25, 5],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(
                ['tenant_id' => $tenant->id, 'sku' => $p[2]],
                [
                    'tenant_id' => $tenant->id,
                    'category_id' => $p[1],
                    'name' => $p[0],
                    'sku' => $p[2],
                    'barcode' => $p[3],
                    'unit' => $p[4],
                    'purchase_price' => $p[5],
                    'selling_price' => $p[6],
                    'wholesale_price' => $p[7],
                    'gst_rate' => $p[8],
                    'gst_type' => $p[9],
                    'stock_quantity' => $p[10],
                    'reorder_level' => $p[11],
                    'is_active' => true,
                ]
            );
        }
    }
}
