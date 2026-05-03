<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    private static array $shopNames = [
        'Sri Murugan Stores', 'Lakshmi General Traders', 'Ganesh Kirana', 'Saraswathi Provisions',
        'Balaji Super Market', 'Devi Departmental Store', 'Ram Stores', 'Shiva Fancy Store',
        'Parvathi Silk House', 'Krishnan Medical & General',
    ];

    public function definition(): array
    {
        $states = ['Tamil Nadu', 'Maharashtra', 'Karnataka', 'Kerala', 'Andhra Pradesh'];
        $cities = ['Chennai', 'Mumbai', 'Bengaluru', 'Coimbatore', 'Madurai'];

        return [
            'shop_name' => $this->faker->randomElement(self::$shopNames),
            'owner_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '9' . $this->faker->numerify('#########'),
            'gstin' => $this->faker->numerify('33AABCU####R1ZX'),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->randomElement($cities),
            'state' => $this->faker->randomElement($states),
            'pincode' => $this->faker->numerify('6#####'),
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'plan_id' => Plan::factory(),
            'plan_status' => 'active',
            'trial_ends_at' => null,
            'is_active' => true,
        ];
    }

    public function onTrial(): static
    {
        return $this->state([
            'plan_status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'plan_status' => 'expired',
            'trial_ends_at' => now()->subDays(1),
        ]);
    }
}
