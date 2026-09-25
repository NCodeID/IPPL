<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::inRandomOrder()->firstOrFail();
        $quantity = fake()->numberBetween(1, 10);

        return [
            'order_id' => Order::inRandomOrder()->value('id'),
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => $quantity,
            'subtotal' => round((float) $product->price * $quantity, 2),
            'note' => fake()->boolean(25) ? fake()->sentence() : null,
        ];
    }
}
