<?php

declare(strict_types=1);

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    #[OA\Post(
        path: '/api/orders',
        operationId: 'orderStore',
        summary: 'Membuat order baru',
        description: 'Membuat order beserta item-nya. Harga, subtotal, total, nomor order, status, dan runner ditentukan oleh server. Stok bahan baku divalidasi dan dikurangi secara atomik.',
        tags: ['Orders'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_type', 'customer_name', 'items'],
                properties: [
                    new OA\Property(property: 'order_type', type: 'string', enum: ['dine_in', 'takeaway']),
                    new OA\Property(property: 'customer_name', type: 'string', example: 'Budi'),
                    new OA\Property(property: 'table_id', type: 'integer', nullable: true, description: 'Wajib untuk dine_in dan tidak boleh diisi untuk takeaway'),
                    new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Tanpa pedas'),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            required: ['product_id', 'quantity'],
                            properties: [
                                new OA\Property(property: 'product_id', type: 'integer'),
                                new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
                            ],
                            type: 'object',
                        ),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Order berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'order_number', type: 'string'),
                                new OA\Property(property: 'customer_name', type: 'string'),
                                new OA\Property(property: 'order_type', type: 'string', enum: ['dine_in', 'takeaway']),
                                new OA\Property(property: 'status', type: 'string', example: 'pending'),
                                new OA\Property(property: 'note', type: 'string', nullable: true),
                                new OA\Property(property: 'cancellation_reason', type: 'string', nullable: true),
                                new OA\Property(
                                    property: 'table',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'number', type: 'integer'),
                                    ],
                                    type: 'object',
                                    nullable: true,
                                ),
                                new OA\Property(
                                    property: 'runner',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'name', type: 'string'),
                                    ],
                                    type: 'object',
                                ),
                                new OA\Property(
                                    property: 'chef',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'name', type: 'string'),
                                    ],
                                    type: 'object',
                                    nullable: true,
                                ),
                                new OA\Property(property: 'subtotal', type: 'integer'),
                                new OA\Property(property: 'discount', type: 'integer'),
                                new OA\Property(property: 'tax', type: 'integer'),
                                new OA\Property(property: 'total', type: 'integer'),
                                new OA\Property(
                                    property: 'items',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer'),
                                            new OA\Property(
                                                property: 'product',
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'integer'),
                                                    new OA\Property(property: 'name', type: 'string'),
                                                ],
                                                type: 'object',
                                            ),
                                            new OA\Property(property: 'unit_price', type: 'integer'),
                                            new OA\Property(property: 'quantity', type: 'integer'),
                                            new OA\Property(property: 'subtotal', type: 'integer'),
                                        ],
                                        type: 'object',
                                    ),
                                ),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object',
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission manage_orders atau akun tidak aktif'),
            new OA\Response(response: 422, description: 'Validasi gagal, meja tidak tersedia, produk tidak dapat dipesan, atau stok tidak mencukupi'),
        ],
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_type' => ['required', 'string', 'in:dine_in,takeaway'],
            'customer_name' => ['required', 'string', 'max:255'],
            'table_id' => [
                'nullable',
                'integer',
                'exists:tables,id',
                'required_if:order_type,dine_in',
                'prohibited_if:order_type,takeaway',
            ],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $runner = $request->user();

        $order = DB::transaction(function () use ($validated, $runner): Order {
            $table = null;

            if ($validated['order_type'] === 'dine_in') {
                $table = Table::query()
                    ->whereKey($validated['table_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $table || ! $table->is_active) {
                    throw ValidationException::withMessages([
                        'table_id' => 'Meja tidak tersedia.',
                    ]);
                }

                if ($table->activeOrders()->exists()) {
                    throw ValidationException::withMessages([
                        'table_id' => 'Meja sedang digunakan.',
                    ]);
                }
            }

            $products = $this->reserveStock($validated['items']);

            $subtotal = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];
                $unitPrice = (int) $product->price;
                $subtotal += $unitPrice * $quantity;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                ];
            }

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'runner_id' => $runner->id,
                'table_id' => $table?->id,
                'customer_name' => $validated['customer_name'],
                'order_type' => $validated['order_type'],
                'status' => Order::STATUS_PENDING,
                'note' => $validated['note'] ?? null,
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => 0,
            ]);

            $order->items()->createMany($orderItems);

            return $order->fresh(['items.product', 'table', 'runner', 'chef']);
        }, attempts: 3);

        return response()->json([
            'data' => $this->transformOrder($order),
        ], 201);
    }

    private function reserveStock(array $items): Collection
    {
        $quantities = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantities[$productId] = ($quantities[$productId] ?? 0) + (int) $item['quantity'];
        }

        $products = Product::query()
            ->with(['category', 'recipes.ingredient'])
            ->whereIn('id', array_keys($quantities))
            ->get()
            ->keyBy('id');

        $errors = [];
        $demand = [];
        $ingredientNames = [];

        foreach ($quantities as $productId => $quantity) {
            $product = $products->get($productId);

            if (! $product) {
                $errors[] = "Produk dengan ID {$productId} tidak ditemukan.";

                continue;
            }

            $evaluation = $this->evaluateOrderability($product, $quantity);

            if (! $evaluation['is_orderable']) {
                $errors[] = $this->unavailableMessage($product->name, $evaluation['unavailable_reason']);

                continue;
            }

            foreach ($product->recipes as $recipe) {
                $ingredientId = $recipe->ingredient_id;
                $required = bcmul((string) $recipe->quantity_required, (string) $quantity, 2);
                $demand[$ingredientId] = bcadd($demand[$ingredientId] ?? '0', $required, 2);
                $ingredientNames[$ingredientId] = $recipe->ingredient->name;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['items' => $errors]);
        }

        if ($demand === []) {
            return $products;
        }

        $ingredientIds = array_keys($demand);
        sort($ingredientIds);

        $ingredients = Ingredient::query()
            ->whereIn('id', $ingredientIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $stockErrors = [];

        foreach ($demand as $ingredientId => $required) {
            $ingredient = $ingredients->get($ingredientId);

            if (! $ingredient || ! $ingredient->is_active) {
                $stockErrors[] = "Bahan baku {$ingredientNames[$ingredientId]} tidak dapat digunakan.";

                continue;
            }

            if (bccomp($required, (string) $ingredient->stock, 2) > 0) {
                $stockErrors[] = "Stok {$ingredient->name} tidak mencukupi untuk pesanan ini.";
            }
        }

        if ($stockErrors !== []) {
            throw ValidationException::withMessages(['stock' => $stockErrors]);
        }

        foreach ($demand as $ingredientId => $required) {
            $affected = DB::update(
                'UPDATE ingredients SET stock = stock - ? WHERE id = ? AND stock >= ?',
                [$required, $ingredientId, $required],
            );

            if ($affected === 0) {
                throw ValidationException::withMessages([
                    'stock' => ["Stok {$ingredientNames[$ingredientId]} tidak mencukupi untuk pesanan ini."],
                ]);
            }
        }

        return $products;
    }

    private function evaluateOrderability(Product $product, int $quantity = 1): array
    {
        if (! $product->is_active) {
            return $this->unavailable('product_inactive');
        }

        if (! $product->category || ! $product->category->is_active) {
            return $this->unavailable('category_inactive');
        }

        if (! $product->is_available) {
            return $this->unavailable('not_available');
        }

        $recipes = $product->recipes;

        if ($recipes->isEmpty()) {
            return $this->unavailable('recipe_missing');
        }

        foreach ($recipes as $recipe) {
            if (! $recipe->ingredient || bccomp((string) $recipe->quantity_required, '0', 2) <= 0) {
                return $this->unavailable('recipe_invalid');
            }
        }

        foreach ($recipes as $recipe) {
            if (! $recipe->ingredient->is_active) {
                return $this->unavailable('ingredient_inactive');
            }
        }

        foreach ($recipes as $recipe) {
            $required = bcmul((string) $recipe->quantity_required, (string) $quantity, 2);

            if (bccomp($required, (string) $recipe->ingredient->stock, 2) > 0) {
                return $this->unavailable('insufficient_stock');
            }
        }

        return [
            'is_orderable' => true,
            'unavailable_reason' => null,
        ];
    }

    private function transformOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'order_type' => $order->order_type,
            'status' => $order->status,
            'note' => $order->note,
            'cancellation_reason' => $order->cancellation_reason,
            'table' => $order->table === null ? null : [
                'id' => $order->table->id,
                'number' => $order->table->number,
            ],
            'runner' => $order->runner === null ? null : [
                'id' => $order->runner->id,
                'name' => $order->runner->name,
            ],
            'chef' => $order->chef === null ? null : [
                'id' => $order->chef->id,
                'name' => $order->chef->name,
            ],
            'subtotal' => $order->subtotal,
            'discount' => $order->discount,
            'tax' => $order->tax,
            'total' => $order->total,
            'items' => $order->items
                ->map(fn (OrderItem $item): array => $this->transformOrderItem($item))
                ->all(),
            'created_at' => $order->created_at?->toISOString(),
        ];
    }

    private function transformOrderItem(OrderItem $item): array
    {
        return [
            'id' => $item->id,
            'product' => [
                'id' => $item->product?->id,
                'name' => $item->product?->name,
            ],
            'unit_price' => $item->unit_price,
            'quantity' => $item->quantity,
            'subtotal' => $item->subtotal,
        ];
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4));
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    private function unavailable(string $reason): array
    {
        return [
            'is_orderable' => false,
            'unavailable_reason' => $reason,
        ];
    }

    private function unavailableMessage(string $productName, string $reason): string
    {
        return match ($reason) {
            'product_inactive' => "Produk {$productName} tidak aktif.",
            'category_inactive' => "Kategori produk {$productName} tidak aktif.",
            'not_available' => "Produk {$productName} sedang tidak tersedia.",
            'recipe_missing' => "Resep produk {$productName} belum dikonfigurasi.",
            'recipe_invalid' => "Resep produk {$productName} tidak valid.",
            'ingredient_inactive' => "Bahan baku produk {$productName} tidak aktif.",
            default => "Stok bahan baku produk {$productName} tidak mencukupi.",
        };
    }
}
