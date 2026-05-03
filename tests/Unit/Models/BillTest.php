<?php

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->plan = Plan::factory()->starter()->create();
    $this->tenant = Tenant::factory()->create(['plan_id' => $this->plan->id]);
    $this->user = User::factory()->cashier()->create(['tenant_id' => $this->tenant->id]);
});

test('bill number format is correct', function () {
    $bill = Bill::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'billed_at' => now(),
    ]);

    expect($bill->bill_number)->toMatch('/^SB-\d{4}-\d{5}$/');
});

test('bill number sequences per tenant independently', function () {
    $otherTenant = Tenant::factory()->create(['plan_id' => $this->plan->id]);
    $otherUser = User::factory()->cashier()->create(['tenant_id' => $otherTenant->id]);

    $bill1 = Bill::factory()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->user->id, 'billed_at' => now()]);
    $bill2 = Bill::factory()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->user->id, 'billed_at' => now()]);
    $otherBill = Bill::factory()->create(['tenant_id' => $otherTenant->id, 'user_id' => $otherUser->id, 'billed_at' => now()]);

    $seq1 = (int) substr($bill1->bill_number, -5);
    $seq2 = (int) substr($bill2->bill_number, -5);
    $seqOther = (int) substr($otherBill->bill_number, -5);

    expect($seq2)->toBe($seq1 + 1);
    expect($seqOther)->toBe(1); // resets for different tenant
});

test('profit amount accessor returns correct value', function () {
    $product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
        'selling_price' => 100,
        'purchase_price' => 60,
    ]);

    $bill = Bill::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'billed_at' => now(),
    ]);

    BillItem::create([
        'bill_id' => $bill->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price' => 100,
        'purchase_price' => 60,
        'quantity' => 2,
        'total' => 200,
    ]);

    $bill->load('items');
    expect($bill->profit_amount)->toBe(80.0); // (100-60)*2
});
