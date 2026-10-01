<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    private const ALLOWED_TRANSITIONS = [
        'pending' => ['cooking', 'cancelled'],
        'cooking' => ['ready'],
        'ready' => ['served'],
        'served' => ['completed'],
    ];

    public function createOrder(array $data, string $runnerId): Order
    {
        return DB::transaction(function () use ($data, $runnerId): Order {
            if ($data['order_type'] === 'dine_in') {
                $table = Table::lockForUpdate()->findOrFail($data['table_id']);

                if ($table->status !== Table::STATUS_AVAILABLE) {
                    throw ValidationException::withMessages([
                        'table_id' => ['The selected table is not available.'],
                    ])->status(409);
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
                $product = Product::with('recipes.ingredient')
                    ->lockForUpdate()
                    ->findOrFail($item['product_id']);

                $hasRecipes = $product->recipes->isNotEmpty();

                if ($hasRecipes) {
                    foreach ($product->recipes as $recipe) {
                        $ingredient = Product::lockForUpdate()->findOrFail($recipe->ingredient_id);

                        $requiredQty = $recipe->quantity_required * $item['quantity'];

                        if ($ingredient->stock < $requiredQty) {
                            throw ValidationException::withMessages([
                                'items' => ["Stok bahan baku {$ingredient->name} tidak mencukupi untuk menu {$product->name}."],
                            ])->status(422);
                        }

                        $ingredient->decrement('stock', $requiredQty);
                    }
                } else {
                    if ($product->stock < $item['quantity']) {
                        throw ValidationException::withMessages([
                            'items' => ["Insufficient stock for product: {$product->name}."],
                        ])->status(422);
                    }

                    $product->decrement('stock', $item['quantity']);
                }

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
        $currentStatus = $order->status;

        if (! isset(self::ALLOWED_TRANSITIONS[$currentStatus])) {
            throw ValidationException::withMessages([
                'status' => ['Status tidak valid untuk transisi.'],
            ])->status(422);
        }

        if (! in_array($newStatus, self::ALLOWED_TRANSITIONS[$currentStatus], true)) {
            throw ValidationException::withMessages([
                'status' => ['Status tidak dapat dikembalikan ke tahap sebelumnya.'],
            ])->status(422);
        }

        $order->status = $newStatus;

        if ($newStatus === 'cooking' && $order->chef_id === null) {
            $order->chef_id = $actorId;
        }

        $order->save();

        return $order;
    }
}
