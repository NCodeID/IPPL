<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Table;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder(array $data, string $runnerId): Order
    {
        return DB::transaction(function () use ($data, $runnerId): Order {
            if ($data['order_type'] === 'dine_in') {
                $table = Table::lockForUpdate()->findOrFail($data['table_id']);

                if ($table->status !== Table::STATUS_AVAILABLE) {
                    throw new Exception('The selected table is not available.');
                }

                $table->update(['status' => Table::STATUS_OCCUPIED]);
            }

            $order = Order::create([
                'order_number' => 'ORD-'.date('YmdHis').'-'.rand(100, 999),
                'runner_id' => $runnerId,
                'table_id' => $data['table_id'] ?? null,
                'customer_name' => $data['customer_name'],
                'order_type' => $data['order_type'],
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'subtotal' => 0,
                'discount' => 0,
                'tax' => 0,
                'total' => 0,
            ]);

            $subtotal = 0;

            foreach ($data['items'] as $item) {
                $product = Product::with('recipes.ingredient')->findOrFail($item['product_id']);
                $lineSubtotal = (int) round($product->price * $item['quantity']);
                $subtotal = $subtotal + $lineSubtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $lineSubtotal,
                    'note' => $item['note'] ?? null,
                ]);

                foreach ($product->recipes as $recipe) {
                    $ingredient = $recipe->ingredient;
                    $ingredient->stock = $ingredient->stock - ($recipe->quantity_required * $item['quantity']);

                    if ($ingredient->stock < 0) {
                        throw new Exception('Insufficient stock for product: '.$ingredient->name);
                    }

                    $ingredient->save();
                }
            }

            $tax = (int) round($subtotal * 0.11);
            $discount = 0;
            $total = (int) round($subtotal - $discount + $tax);

            $order->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
            ]);

            return $order->load(['orderItems', 'table']);
        });
    }

    public function updateStatus(Order $order, string $newStatus, string $actorId): Order
    {
        $order->status = $newStatus;

        if ($newStatus === 'cooking' || $newStatus === 'ready') {
            $order->chef_id = $actorId;
        }

        $order->save();

        return $order;
    }
}
