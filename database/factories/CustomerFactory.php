<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    private static array $indianNames = [
        'Rajan Kumar', 'Meena Devi', 'Suresh Babu', 'Kavitha Rajesh',
        'Murugesan P', 'Lakshmi Priya', 'Selvam T', 'Geetha Krishnan',
        'Anand Raj', 'Vijayalakshmi S',
    ];

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->randomElement(self::$indianNames),
            'phone' => '9' . $this->faker->numerify('#########'),
            'email' => $this->faker->optional(0.4)->safeEmail(),
            'address' => $this->faker->optional(0.6)->streetAddress(),
            'credit_limit' => $this->faker->randomElement([0, 1000, 2000, 5000]),
            'credit_balance' => 0,
        ];
    }

    public function withDues(float $amount = 500.00): static
    {
        return $this->state([
            'credit_balance' => $amount,
            'credit_limit' => $amount * 2,
        ]);
    }
}
