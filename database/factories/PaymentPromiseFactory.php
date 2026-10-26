<?php

namespace Database\Factories;

use App\Models\Creditor;
use App\Models\PaymentPromise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentPromise>
 */
class PaymentPromiseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creditor_id' => Creditor::factory(),
            'direction' => 'receivable', 'currency' => 'YER',
            'amount_minor' => fake()->numberBetween(100, 100000),
            'due_at' => today()->addDays(3)->toDateString(), 'status' => 'pending',
        ];
    }
}
