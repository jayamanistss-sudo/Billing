<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('email', 'demo@srimuruganstores.com')->firstOrFail();

        User::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'owner@demo.com'],
            ['tenant_id' => $tenant->id, 'name' => 'Murugesan Pillai', 'email' => 'owner@demo.com', 'phone' => '9876543210', 'password' => Hash::make('password'), 'role' => 'owner', 'is_active' => true]
        );

        User::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'mgr@demo.com'],
            ['tenant_id' => $tenant->id, 'name' => 'Rajan Kumar', 'email' => 'mgr@demo.com', 'phone' => '9876543211', 'password' => Hash::make('password'), 'role' => 'manager', 'is_active' => true]
        );

        User::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'cashier@demo.com'],
            ['tenant_id' => $tenant->id, 'name' => 'Selvam T', 'email' => 'cashier@demo.com', 'phone' => '9876543212', 'password' => Hash::make('password'), 'pin' => Hash::make('123456'), 'role' => 'cashier', 'is_active' => true]
        );
    }
}
