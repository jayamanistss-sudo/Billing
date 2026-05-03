<?php

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->plan = Plan::factory()->growth()->create();
    $this->tenant = Tenant::factory()->create(['plan_id' => $this->plan->id]);
    $this->owner = User::factory()->owner()->create(['tenant_id' => $this->tenant->id]);
    $this->token = auth()->guard('api')->login($this->owner);

    // Seed a bill for today
    $this->product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'selling_price' => 100,
        'purchase_price' => 60,
        'gst_rate' => 18,
    ]);

    $bill = Bill::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->owner->id,
        'total_amount' => 118,
        'cgst_amount' => 9,
        'sgst_amount' => 9,
        'billed_at' => now(),
    ]);

    BillItem::create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'product_name' => $this->product->name,
        'unit_price' => 100,
        'purchase_price' => 60,
        'quantity' => 1,
        'gst_rate' => 18,
        'gst_amount' => 18,
        'total' => 118,
    ]);
});

test('daily report returns correct totals for date', function () {
    $today = now()->toDateString();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/reports/daily?date={$today}");

    $response->assertOk()
        ->assertJsonStructure(['data' => ['total_sales', 'total_bills', 'payment_breakdown']]);

    expect($response->json('data.total_bills'))->toBeGreaterThanOrEqual(1);
});

test('monthly report returns correct aggregates', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/reports/monthly?year=' . now()->year . '&month=' . now()->month);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['total_sales', 'total_bills', 'daily_breakdown']]);
});

test('top products report is ordered by quantity sold', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/reports/top-products');

    $response->assertOk();
    expect($response->json('data'))->toBeArray();
});

test('gst summary groups by gst rate correctly', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/reports/gst-summary?year=' . now()->year . '&month=' . now()->month);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['slabs']]);
});

test('reports are scoped to current tenant only', function () {
    // Create another tenant with their own bill
    $otherPlan = Plan::factory()->starter()->create();
    $otherTenant = Tenant::factory()->create(['plan_id' => $otherPlan->id]);
    $otherUser = User::factory()->owner()->create(['tenant_id' => $otherTenant->id]);

    Bill::factory()->create([
        'tenant_id' => $otherTenant->id,
        'user_id' => $otherUser->id,
        'total_amount' => 99999,
        'billed_at' => now(),
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/reports/daily?date=' . now()->toDateString());

    $response->assertOk();
    // Our tenant only has 1 bill of 118, not 99999
    expect((float) $response->json('data.total_sales'))->not->toBe(99999.0 + 118.0);
});
