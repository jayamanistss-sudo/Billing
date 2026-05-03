<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plan::where('slug', 'growth')->firstOrFail();

        Tenant::updateOrCreate(
            ['email' => 'demo@srimuruganstores.com'],
            [
                'shop_name' => 'Sri Murugan Stores',
                'owner_name' => 'Murugesan Pillai',
                'email' => 'demo@srimuruganstores.com',
                'phone' => '9876543210',
                'gstin' => '33AABCU9603R1ZX',
                'address' => '45, Anna Nagar Main Road',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'pincode' => '600040',
                'receipt_footer' => 'Thank you for shopping with us! Visit again.',
                'currency' => 'INR',
                'timezone' => 'Asia/Kolkata',
                'plan_id' => $plan->id,
                'plan_status' => 'active',
                'is_active' => true,
            ]
        );
    }
}
