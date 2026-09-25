<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $order = Order::inRandomOrder()->firstOrFail();
        $amount = round((float) $order->total, 2);
        $tendered = round($amount + fake()->randomFloat(2, 0, $amount * 0.3), 2);
        $status = fake()->randomElement(['completed', 'completed', 'pending', 'failed']);

        return [
            'order_id' => $order->id,
            'payment_method_id' => PaymentMethod::inRandomOrder()->value('id'),
            'amount' => $amount,
            'tendered_amount' => $tendered,
            'change_amount' => round($tendered - $amount, 2),
            'status' => $status,
            'paid_at' => $status === 'completed' ? fake()->dateTimeBetween('-30 days') : null,
        ];
    }
}
