<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Starter', 'Growth', 'Pro']),
            'slug' => $this->faker->unique()->slug(1),
            'price_monthly' => $this->faker->randomElement([29900, 59900, 99900]),
            'max_devices' => 1,
            'max_products' => 500,
            'max_staff' => 2,
            'whatsapp_receipt' => false,
            'multi_branch' => false,
            'api_access' => false,
        ];
    }

    public function starter(): static
    {
        return $this->state(['name' => 'Starter', 'slug' => 'starter', 'price_monthly' => 29900, 'max_products' => 500, 'max_staff' => 1]);
    }

    public function growth(): static
    {
        return $this->state(['name' => 'Growth', 'slug' => 'growth', 'price_monthly' => 59900, 'max_products' => 2000, 'max_staff' => 5, 'whatsapp_receipt' => true]);
    }

    public function pro(): static
    {
        return $this->state(['name' => 'Pro', 'slug' => 'pro', 'price_monthly' => 99900, 'max_products' => -1, 'max_staff' => -1, 'whatsapp_receipt' => true, 'multi_branch' => true, 'api_access' => true]);
    }
}
