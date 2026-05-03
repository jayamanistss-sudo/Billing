<?php

use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;

const PRODUCTS_URL = '/api/v1/products';
const STOCK_ADJUST_URL = '/api/v1/stock-movements/adjust';

beforeEach(function () {
    $this->plan = Plan::factory()->growth()->create();
    $this->tenant = Tenant::factory()->create(['plan_id' => $this->plan->id]);
    $this->owner = User::factory()->owner()->create(['tenant_id' => $this->tenant->id]);
    $this->cashier = User::factory()->cashier()->create(['tenant_id' => $this->tenant->id]);
});

function ownerToken($owner): string   { return auth()->guard('api')->login($owner); }
function cashierToken($cashier): string { return auth()->guard('api')->login($cashier); }

test('owner can create product', function () {
    $token = ownerToken($this->owner);
    $this->withHeader('Authorization', "Bearer $token")
        ->postJson(PRODUCTS_URL, ['name' => 'Test Product', 'selling_price' => 100, 'stock_quantity' => 50, 'reorder_level' => 5])
        ->assertStatus(201)
        ->assertJson(['success' => true]);
});

test('cashier cannot create product', function () {
    $token = cashierToken($this->cashier);
    $this->withHeader('Authorization', "Bearer $token")
        ->postJson(PRODUCTS_URL, ['name' => 'Test Product', 'selling_price' => 100, 'stock_quantity' => 50, 'reorder_level' => 5])
        ->assertStatus(403);
});

test('product barcode must be unique per tenant', function () {
    Product::factory()->create(['tenant_id' => $this->tenant->id, 'barcode' => '1234567890123']);

    $token = ownerToken($this->owner);
    $this->withHeader('Authorization', "Bearer $token")
        ->postJson(PRODUCTS_URL, ['name' => 'Another', 'barcode' => '1234567890123', 'selling_price' => 50, 'stock_quantity' => 10, 'reorder_level' => 2])
        ->assertStatus(422);
});

test('product search returns matching results', function () {
    Product::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Tata Salt']);
    Product::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Amul Butter']);

    $token = ownerToken($this->owner);
    $response = $this->withHeader('Authorization', "Bearer $token")
        ->getJson(PRODUCTS_URL . '?search=Tata');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.name'))->toBe('Tata Salt');
});

test('low stock endpoint returns only products below reorder level', function () {
    Product::factory()->create(['tenant_id' => $this->tenant->id, 'stock_quantity' => 2, 'reorder_level' => 5]);
    Product::factory()->create(['tenant_id' => $this->tenant->id, 'stock_quantity' => 100, 'reorder_level' => 5]);

    $token = ownerToken($this->owner);
    $response = $this->withHeader('Authorization', "Bearer $token")
        ->getJson('/api/v1/products/low-stock');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

test('stock adjustment creates stock movement', function () {
    $manager = User::factory()->manager()->create(['tenant_id' => $this->tenant->id]);
    $managerToken = auth()->guard('api')->login($manager);
    $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'stock_quantity' => 50]);

    $this->withHeader('Authorization', "Bearer $managerToken")
        ->postJson(STOCK_ADJUST_URL, ['product_id' => $product->id, 'type' => 'purchase', 'quantity' => 20, 'notes' => 'Restocking'])
        ->assertStatus(201);

    $product->refresh();
    expect($product->stock_quantity)->toBe(70);
});

test('manager can adjust stock', function () {
    $manager = User::factory()->manager()->create(['tenant_id' => $this->tenant->id]);
    $managerToken = auth()->guard('api')->login($manager);
    $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->withHeader('Authorization', "Bearer $managerToken")
        ->postJson(STOCK_ADJUST_URL, ['product_id' => $product->id, 'type' => 'adjustment', 'quantity' => 5])
        ->assertStatus(201);
});

test('cashier cannot adjust stock', function () {
    $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
    $token = cashierToken($this->cashier);

    $this->withHeader('Authorization', "Bearer $token")
        ->postJson(STOCK_ADJUST_URL, ['product_id' => $product->id, 'type' => 'purchase', 'quantity' => 10])
        ->assertStatus(403);
});
