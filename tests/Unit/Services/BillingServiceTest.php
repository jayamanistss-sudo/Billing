<?php

use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\BillingService;
use App\Repositories\BillRepository;

beforeEach(function () {
    $this->plan = Plan::factory()->starter()->create();
    $this->tenant = Tenant::factory()->create(['plan_id' => $this->plan->id]);
    $this->service = app(BillingService::class);
});

test('calculate totals with zero discount', function () {
    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'selling_price' => 100.00,
        'gst_rate' => 0,
        'gst_type' => 'exclusive',
    ]);

    $result = $this->service->calculateTotals(
        [['product_id' => $product->id, 'quantity' => 2]],
        0,
        0,
        'retail'
    );

    expect($result['subtotal'])->toBe(200.00);
    expect($result['discount_amount'])->toBe(0.0);
    expect($result['total_amount'])->toBe(200.00);
});

test('calculate totals with percentage discount', function () {
    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'selling_price' => 100.00,
        'gst_rate' => 0,
        'gst_type' => 'exclusive',
    ]);

    $result = $this->service->calculateTotals(
        [['product_id' => $product->id, 'quantity' => 1]],
        10,
        0,
        'retail'
    );

    expect($result['discount_amount'])->toBe(10.00);
    expect($result['total_amount'])->toBe(90.00);
});

test('calculate totals with exclusive gst', function () {
    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'selling_price' => 100.00,
        'gst_rate' => 18,
        'gst_type' => 'exclusive',
    ]);

    $result = $this->service->calculateTotals(
        [['product_id' => $product->id, 'quantity' => 1]],
        0,
        0,
        'retail'
    );

    expect($result['cgst_amount'])->toBe(9.00);
    expect($result['sgst_amount'])->toBe(9.00);
    expect($result['total_amount'])->toBe(118.00);
});

test('calculate totals with extra charges', function () {
    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'selling_price' => 100.00,
        'gst_rate' => 0,
        'gst_type' => 'exclusive',
    ]);

    $result = $this->service->calculateTotals(
        [['product_id' => $product->id, 'quantity' => 1]],
        0,
        20,
        'retail'
    );

    expect($result['total_amount'])->toBe(120.00);
});

test('cgst and sgst are equal halves of total gst', function () {
    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'selling_price' => 200.00,
        'gst_rate' => 12,
        'gst_type' => 'exclusive',
    ]);

    $result = $this->service->calculateTotals(
        [['product_id' => $product->id, 'quantity' => 1]],
        0,
        0,
        'retail'
    );

    expect($result['cgst_amount'])->toBe($result['sgst_amount']);
    expect($result['cgst_amount'] + $result['sgst_amount'])->toBe(24.00);
});
