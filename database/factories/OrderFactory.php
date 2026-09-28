<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10000, 5000000);
        $discount = fake()->randomFloat(2, 0, round($subtotal * 0.2, 2));
        $tax = round($subtotal * 0.11, 2);
        $status = fake()->randomElement(['pending', 'completed', 'completed', 'cancelled']);

        return [
            'order_number' => fake()->unique()->bothify('ORD-######'),
            'runner_id' => User::inRandomOrder()->value('id'),
            'chef_id' => fake()->boolean(70) ? User::inRandomOrder()->value('id') : null,
            'table_id' => Table::inRandomOrder()->value('id'),
            'customer_name' => fake()->boolean(60) ? fake()->name() : null,
            'order_type' => fake()->randomElement(['dine_in', 'takeaway']),
            'status' => $status,
            'payment_status' => fake()->randomElement(['paid', 'unpaid', 'partial']),
            'note' => fake()->boolean(30) ? fake()->sentence() : null,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => round($subtotal - $discount + $tax, 2),
            'completed_at' => $status === 'completed' ? fake()->dateTimeBetween('-60 days') : null,
        ];
    }
}
