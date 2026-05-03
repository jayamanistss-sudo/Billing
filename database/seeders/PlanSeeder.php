<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'             => 'Free',
                'slug'             => 'free',
                'price_monthly'    => 0,
                'max_devices'      => 1,
                'max_products'     => 100,
                'max_staff'        => 1,
                'whatsapp_receipt' => false,
                'multi_branch'     => false,
                'api_access'       => false,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'price_monthly' => 29900,
                'max_devices' => 1,
                'max_products' => 500,
                'max_staff' => 1,
                'whatsapp_receipt' => false,
                'multi_branch' => false,
                'api_access' => false,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'price_monthly' => 59900,
                'max_devices' => 3,
                'max_products' => 2000,
                'max_staff' => 5,
                'whatsapp_receipt' => true,
                'multi_branch' => false,
                'api_access' => false,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price_monthly' => 99900,
                'max_devices' => -1,
                'max_products' => -1,
                'max_staff' => -1,
                'whatsapp_receipt' => true,
                'multi_branch' => true,
                'api_access' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
