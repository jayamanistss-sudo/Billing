<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillFactory extends Factory
{
    protected $model = Bill::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 50, 2000);
        $discountPct = $this->faker->randomElement([0, 0, 0, 5, 10]);
        $discountAmount = round($subtotal * $discountPct / 100, 2);
        $taxable = $subtotal - $discountAmount;
        $cgst = round($taxable * 0.025, 2);
        $sgst = $cgst;
        $total = $taxable + $cgst + $sgst;

        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'bill_type' => 'retail',
            'subtotal' => $subtotal,
            'discount_percent' => $discountPct,
            'discount_amount' => $discountAmount,
            'extra_charges' => 0,
            'cgst_amount' => $cgst,
            'sgst_amount' => $sgst,
            'igst_amount' => 0,
            'total_amount' => $total,
            'paid_amount' => $total,
            'due_amount' => 0,
            'payment_status' => 'paid',
            'payment_method' => $this->faker->randomElement(['cash', 'upi', 'card']),
            'billed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
