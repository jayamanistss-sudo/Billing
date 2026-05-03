<?php

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    Plan::factory()->starter()->create();
});

test('owner can register shop and receive token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'shop_name' => 'Test Store',
        'owner_name' => 'Test Owner',
        'email' => 'owner@test.com',
        'phone' => '9876543210',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success', 'data' => ['token', 'user', 'tenant'],
        ])
        ->assertJson(['success' => true]);

    expect(Tenant::where('email', 'owner@test.com')->exists())->toBeTrue();
    expect(User::where('email', 'owner@test.com')->where('role', 'owner')->exists())->toBeTrue();
});

test('login with valid credentials returns token and user', function () {
    $tenant = Tenant::factory()->create(['plan_id' => Plan::first()->id]);
    $user = User::factory()->owner()->create([
        'tenant_id' => $tenant->id,
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'user@test.com',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['token', 'user', 'tenant']])
        ->assertJson(['success' => true]);
});

test('login with invalid credentials returns 401', function () {
    $tenant = Tenant::factory()->create(['plan_id' => Plan::first()->id]);
    User::factory()->owner()->create([
        'tenant_id' => $tenant->id,
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);

    $this->postJson('/api/v1/auth/login', [
        'login' => 'user@test.com',
        'password' => 'wrongpassword',
    ])->assertStatus(401);
});

test('cashier can login with PIN', function () {
    $tenant = Tenant::factory()->create(['plan_id' => Plan::first()->id]);
    User::factory()->cashier()->withPin('123456')->create(['tenant_id' => $tenant->id]);

    $response = $this->postJson('/api/v1/auth/pin-login', [
        'pin' => '123456',
        'tenant_id' => $tenant->id,
    ]);

    $response->assertOk()->assertJson(['success' => true]);
});

test('expired tenant receives 402 on protected routes', function () {
    $plan = Plan::first();
    $tenant = Tenant::factory()->expired()->create(['plan_id' => $plan->id]);
    $user = User::factory()->owner()->create(['tenant_id' => $tenant->id]);

    $token = auth()->login($user);

    $this->withHeader('Authorization', "Bearer $token")
        ->getJson('/api/v1/dashboard')
        ->assertStatus(402);
});

test('refresh token returns new access token', function () {
    $tenant = Tenant::factory()->create(['plan_id' => Plan::first()->id]);
    $user = User::factory()->owner()->create(['tenant_id' => $tenant->id]);
    $token = auth()->login($user);

    $this->withHeader('Authorization', "Bearer $token")
        ->postJson('/api/v1/auth/refresh')
        ->assertOk()
        ->assertJsonStructure(['data' => ['token']]);
});
