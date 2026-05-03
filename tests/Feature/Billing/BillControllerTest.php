<?php

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->plan = Plan::factory()->growth()->create();
    $this->tenant = Tenant::factory()->create(['plan_id' => $this->plan->id]);
    $this->cashier = User::factory()->cashier()->create(['tenant_id' => $this->tenant->id]);
    $this->owner = User::factory()->owner()->create(['tenant_id' => $this->tenant->id]);
    $this->product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'selling_price' => 100.00,
        'purchase_price' => 70.00,
        'gst_rate' => 18,
        'gst_type' => 'exclusive',
        'stock_quantity' => 50,
    ]);
    $this->token = auth()->guard('api')->login($this->cashier);
});

test('cashier can create a retail bill', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/bills', [
            'bill_type' => 'retail',
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2],
            ],
        ]);

    $response->assertStatus(201)->assertJson(['success' => true]);
    expect(Bill::where('tenant_id', $this->tenant->id)->count())->toBe(1);
});

test('bill creation deducts stock correctly', function () {
    $initialStock = $this->product->stock_quantity;

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/bills', [
            'bill_type' => 'retail',
            'payment_method' => 'cash',
            'items' => [['product_id' => $this->product->id, 'quantity' => 3]],
        ]);

    $this->product->refresh();
    expect($this->product->stock_quantity)->toBe($initialStock - 3);
});

test('bill creation creates stock movement records', function () {
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/bills', [
            'bill_type' => 'retail',
            'payment_method' => 'cash',
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
        ]);

    expect(StockMovement::where('tenant_id', $this->tenant->id)->where('type', 'sale')->count())->toBe(1);
});

test('bill with credit type updates customer credit balance', function () {
    $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id, 'credit_balance' => 0]);

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/bills', [
            'bill_type' => 'credit',
            'payment_method' => 'credit',
            'customer_id' => $customer->id,
            'paid_amount' => 0,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ]);

    $customer->refresh();
    expect((float) $customer->credit_balance)->toBeGreaterThan(0);
});

test('bill number is auto-generated in correct format', function () {
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/bills', [
            'bill_type' => 'retail',
            'payment_method' => 'cash',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ]);

    $bill = Bill::where('tenant_id', $this->tenant->id)->first();
    expect($bill->bill_number)->toMatch('/^SB-\d{4}-\d{5}$/');
});

test('cannot create bill if product has insufficient stock', function () {
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/bills', [
            'bill_type' => 'retail',
            'payment_method' => 'cash',
            'items' => [['product_id' => $this->product->id, 'quantity' => 9999]],
        ])
        ->assertStatus(422);
});

test('discount is correctly calculated in bill totals', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/bills', [
            'bill_type' => 'retail',
            'payment_method' => 'cash',
            'discount_percent' => 10,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ]);

    $response->assertStatus(201);
    $bill = Bill::where('tenant_id', $this->tenant->id)->first();
    expect((float) $bill->discount_percent)->toBe(10.0);
    expect((float) $bill->discount_amount)->toBeGreaterThan(0);
});

test('gst amounts are correctly calculated', function () {
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/bills', [
            'bill_type' => 'retail',
            'payment_method' => 'cash',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ]);

    $bill = Bill::where('tenant_id', $this->tenant->id)->first();
    expect((float) $bill->cgst_amount)->toBeGreaterThan(0);
    expect((float) $bill->sgst_amount)->toBe((float) $bill->cgst_amount);
});

test('owner can process a return', function () {
    $ownerToken = auth()->guard('api')->login($this->owner);
    $createResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/bills', [
            'bill_type' => 'retail',
            'payment_method' => 'cash',
            'items' => [['product_id' => $this->product->id, 'quantity' => 3]],
        ]);

    $billId = $createResponse->json('data.id');

    $this->withHeader('Authorization', "Bearer $ownerToken")
        ->postJson("/api/v1/bills/{$billId}/return", [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ])
        ->assertStatus(201);
});

test('cashier cannot process a return', function () {
    $createResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/bills', [
            'bill_type' => 'retail',
            'payment_method' => 'cash',
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
        ]);

    $billId = $createResponse->json('data.id');

    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/v1/bills/{$billId}/return", [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ])
        ->assertStatus(403);
});

test('bill list is filtered by date correctly', function () {
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/bills', [
            'bill_type' => 'retail',
            'payment_method' => 'cash',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ]);

    $today = now()->toDateString();
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/bills?date={$today}");

    $response->assertOk();
    expect($response->json('data'))->not->toBeEmpty();
});

test('bill list is filtered by payment status correctly', function () {
    $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/bills', [
            'bill_type' => 'retail',
            'payment_method' => 'cash',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/bills?payment_status=paid');

    $response->assertOk();
    expect($response->json('data'))->not->toBeEmpty();
});
