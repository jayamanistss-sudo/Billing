<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '9' . $this->faker->numerify('#########'),
            'password' => Hash::make('password'),
            'pin' => null,
            'role' => 'cashier',
            'is_active' => true,
        ];
    }

    public function owner(): static
    {
        return $this->state(['role' => 'owner']);
    }

    public function manager(): static
    {
        return $this->state(['role' => 'manager']);
    }

    public function cashier(): static
    {
        return $this->state(['role' => 'cashier']);
    }

    public function withPin(string $pin = '123456'): static
    {
        return $this->state(['pin' => Hash::make($pin)]);
    }
}
