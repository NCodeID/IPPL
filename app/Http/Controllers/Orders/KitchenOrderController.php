<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'KitchenOrderCard',
    required: ['id', 'order_number', 'customer_name', 'order_type', 'status', 'created_at', 'note', 'chef_id', 'table', 'items'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'order_number', type: 'string'),
        new OA\Property(property: 'customer_name', type: 'string'),
        new OA\Property(property: 'order_type', type: 'string', enum: ['dine_in', 'takeaway']),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'cooking']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'chef_id', type: 'integer', nullable: true),
        new OA\Property(property: 'table', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'number', type: 'integer'),
        ]),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(type: 'object', properties: [
            new OA\Property(property: 'product', type: 'object', properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string', nullable: true),
            ]),
            new OA\Property(property: 'quantity', type: 'integer'),
        ])),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'KitchenTransition',
    properties: [
        new OA\Property(property: 'data', type: 'object', required: ['id', 'status', 'chef_id'], properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'status', type: 'string', enum: ['cooking', 'ready']),
            new OA\Property(property: 'chef_id', type: 'integer', nullable: true),
        ]),
    ],
    type: 'object',
)]
class KitchenOrderController extends Controller
{
    #[OA\Get(
        path: '/api/kitchen/orders',
        operationId: 'kitchenOrderQueue',
        summary: 'Daftar antrean dapur',
        description: 'Akun aktif dengan role dapur dan permission manage_orders. Pending dan cooking, termasuk dine_in dan takeaway, dari pesanan terlama. Jumlah mencakup seluruh antrean.',
        tags: ['Kitchen'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'page', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
            new OA\QueryParameter(name: 'per_page', schema: new OA\Schema(type: 'integer', default: 15, maximum: 100, minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Antrean dapur dengan pagination', content: new OA\JsonContent(type: 'object', properties: [
                new OA\Property(property: 'counts', type: 'object', properties: [
                    new OA\Property(property: 'total', type: 'integer'),
                    new OA\Property(property: 'by_status', type: 'object', properties: [
                        new OA\Property(property: 'pending', type: 'integer'),
                        new OA\Property(property: 'cooking', type: 'integer'),
                    ]),
                ]),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/KitchenOrderCard')),
                new OA\Property(property: 'links', type: 'object', description: 'first, last, prev, next'),
                new OA\Property(property: 'meta', type: 'object', description: 'current_page, from, last_page, links, path, per_page, to, total'),
            ])),
            new OA\Response(response: 401, description: 'Belum terautentikasi'),
            new OA\Response(response: 403, description: 'Akun, role, atau permission tidak memenuhi syarat'),
            new OA\Response(response: 422, description: 'Pagination tidak valid'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'required', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
        ]);

        return DB::transaction(function () use ($validated): JsonResponse {
            $queue = Order::query()->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_COOKING]);
            $counts = (clone $queue)->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status');
            $byStatus = [
                Order::STATUS_PENDING => (int) ($counts[Order::STATUS_PENDING] ?? 0),
                Order::STATUS_COOKING => (int) ($counts[Order::STATUS_COOKING] ?? 0),
            ];
            $total = array_sum($byStatus);
            $orders = $queue->with(['table:id,number', 'items:id,order_id,product_id,quantity', 'items.product:id,name'])
                ->orderBy('created_at')->orderBy('id')
                ->paginate(
                    perPage: (int) ($validated['per_page'] ?? 15),
                    columns: ['id', 'order_number', 'customer_name', 'order_type', 'status', 'created_at', 'note', 'chef_id', 'table_id'],
                    page: isset($validated['page']) ? (int) $validated['page'] : null,
                    total: $total,
                )
                ->appends(array_intersect_key($validated, ['per_page' => true]));

            return response()->json([
                'counts' => ['total' => $total, 'by_status' => $byStatus],
                'data' => $orders->getCollection()->map(fn (Order $order): array => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'order_type' => $order->order_type,
                    'status' => $order->status,
                    'created_at' => $order->created_at?->toISOString(),
                    'note' => $order->note,
                    'chef_id' => $order->chef_id,
                    'table' => $order->order_type === 'dine_in' && $order->table !== null
                        ? ['id' => $order->table->id, 'number' => $order->table->number] : null,
                    'items' => $order->items->map(fn (OrderItem $item): array => [
                        'product' => ['id' => $item->product_id, 'name' => $item->product?->name],
                        'quantity' => $item->quantity,
                    ])->all(),
                ])->all(),
                'links' => [
                    'first' => $orders->url(1),
                    'last' => $orders->url($orders->lastPage()),
                    'prev' => $orders->previousPageUrl(),
                    'next' => $orders->nextPageUrl(),
                ],
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'from' => $orders->firstItem(),
                    'last_page' => $orders->lastPage(),
                    'links' => $orders->linkCollection()->toArray(),
                    'path' => $orders->path(),
                    'per_page' => $orders->perPage(),
                    'to' => $orders->lastItem(),
                    'total' => $orders->total(),
                ],
            ]);
        });
    }

    #[OA\Post(
        path: '/api/kitchen/orders/{order}/accept',
        operationId: 'kitchenAcceptOrder',
        summary: 'Terima pesanan untuk dimasak',
        description: 'Akun aktif dengan role dapur dan permission manage_orders. Tanpa body. Pending tanpa chef menjadi cooking dan mencatat chef pertama.',
        tags: ['Kitchen'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'order', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Pesanan cooking dengan chef pertama', content: new OA\JsonContent(ref: '#/components/schemas/KitchenTransition')),
            new OA\Response(response: 401, description: 'Belum terautentikasi'),
            new OA\Response(response: 403, description: 'Akun, role, atau permission tidak memenuhi syarat'),
            new OA\Response(response: 404, description: 'Pesanan tidak ditemukan'),
            new OA\Response(response: 409, description: 'Pesanan bukan pending atau chef sudah tercatat'),
            new OA\Response(response: 422, description: 'Tidak menerima payload'),
        ],
    )]
    public function accept(Request $request, Order $order): JsonResponse
    {
        return $this->transition($request, $order, Order::STATUS_PENDING, Order::STATUS_COOKING);
    }

    #[OA\Post(
        path: '/api/kitchen/orders/{order}/ready',
        operationId: 'kitchenReadyOrder',
        summary: 'Tandai seluruh pesanan siap',
        description: 'Akun aktif dengan role dapur dan permission manage_orders. Tanpa body. Cooking menjadi ready. Chef pertama tetap tercatat, termasuk null untuk pesanan lama.',
        tags: ['Kitchen'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'order', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Pesanan ready dengan chef yang sama', content: new OA\JsonContent(ref: '#/components/schemas/KitchenTransition')),
            new OA\Response(response: 401, description: 'Belum terautentikasi'),
            new OA\Response(response: 403, description: 'Akun, role, atau permission tidak memenuhi syarat'),
            new OA\Response(response: 404, description: 'Pesanan tidak ditemukan'),
            new OA\Response(response: 409, description: 'Pesanan bukan cooking'),
            new OA\Response(response: 422, description: 'Tidak menerima payload'),
        ],
    )]
    public function ready(Request $request, Order $order): JsonResponse
    {
        return $this->transition($request, $order, Order::STATUS_COOKING, Order::STATUS_READY);
    }

    private function transition(Request $request, Order $order, string $from, string $to): JsonResponse
    {
        if ($request->all() !== []) {
            throw ValidationException::withMessages(['order' => 'Transisi dapur tidak menerima payload.']);
        }

        return DB::transaction(function () use ($request, $order, $from, $to): JsonResponse {
            $target = DB::table('orders')->where('id', $order->id);
            $eligible = (clone $target)->where('status', $from);
            $changes = ['status' => $to];

            if ($from === Order::STATUS_PENDING) {
                $eligible->whereNull('chef_id');
                $changes['chef_id'] = $request->user()->id;
            }

            $updated = $eligible->update($changes);
            // A current read avoids returning the stale chef from route binding.
            $current = $target->lockForUpdate()->first(['id', 'status', 'chef_id']);
            abort_if($current === null, 404);
            abort_unless($updated === 1, 409, 'Status atau penugasan dapur tidak sesuai.');

            return response()->json(['data' => $current]);
        }, attempts: 3);
    }
}
