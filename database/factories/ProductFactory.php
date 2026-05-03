<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    private static array $products = [
        ['Tata Salt 1kg', 5, 22.00, 26.00, 'kg'],
        ['Aashirvaad Atta 5kg', 5, 165.00, 185.00, 'pack'],
        ['Amul Butter 500g', 5, 230.00, 260.00, 'pack'],
        ['Horlicks 500g', 12, 210.00, 240.00, 'pack'],
        ['Colgate Toothpaste 200g', 12, 75.00, 92.00, 'piece'],
        ['Lux Soap 100g', 12, 45.00, 55.00, 'piece'],
        ['Sunflower Oil 1L', 5, 130.00, 148.00, 'litre'],
        ['Maggi Noodles 70g', 0, 12.00, 15.00, 'piece'],
        ['Parle-G Biscuits 800g', 0, 42.00, 50.00, 'pack'],
        ['Good Day Cashew 100g', 18, 27.00, 32.00, 'pack'],
    ];

    public function definition(): array
    {
        $product = $this->faker->randomElement(self::$products);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $product[0],
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'barcode' => $this->faker->unique()->ean13(),
            'unit' => $product[4],
            'purchase_price' => $product[2],
            'selling_price' => $product[3],
            'gst_rate' => $product[1],
            'gst_type' => 'exclusive',
            'stock_quantity' => $this->faker->numberBetween(10, 200),
            'reorder_level' => 5,
            'is_active' => true,
        ];
    }

    public function lowStock(): static
    {
        return $this->state([
            'stock_quantity' => 2,
            'reorder_level' => 5,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock_quantity' => 0]);
    }
}
