<?php

namespace App\Http\Controllers\Tables;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class TableController extends Controller
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    #[OA\Get(
        path: '/api/tables',
        operationId: 'tableIndex',
        summary: 'Menampilkan daftar meja',
        description: 'Mengambil daftar meja aktif beserta order aktif-nya. Mendukung pencarian nomor, filter status, dan pagination.',
        tags: ['Tables'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(
                name: 'page',
                description: 'Nomor halaman',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1),
            ),
            new OA\QueryParameter(
                name: 'per_page',
                description: 'Jumlah data per halaman (default: 15, max: 100)',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100),
            ),
            new OA\QueryParameter(
                name: 'search',
                description: 'Pencarian berdasarkan nomor meja',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\QueryParameter(
                name: 'status',
                description: 'Filter status meja',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['processing', 'occupied', 'available']),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar meja',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Table'),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission view_tables'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in([
                Table::STATUS_AVAILABLE,
                Table::STATUS_PROCESSING,
                Table::STATUS_OCCUPIED,
            ])],
        ]);

        $tables = Table::query()
            ->where('is_active', true)
            ->when(
                $validated['search'] ?? null,
                fn (Builder $query, string $search): Builder => $query->where('number', 'like', "%{$search}%"),
            )
            ->when(
                $validated['status'] ?? null,
                fn (Builder $query, string $status): Builder => $this->applyStatusFilter($query, $status),
            )
            ->with('activeOrders')
            ->orderBy('number')
            ->paginate(perPage: (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE));

        return response()->json([
            'data' => $tables->getCollection()
                ->map(fn (Table $table): array => $this->transformTable($table))
                ->all(),
            'links' => [
                'first' => $tables->url(1),
                'last' => $tables->url($tables->lastPage()),
                'prev' => $tables->previousPageUrl(),
                'next' => $tables->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $tables->currentPage(),
                'from' => $tables->firstItem(),
                'last_page' => $tables->lastPage(),
                'links' => $tables->linkCollection()->toArray(),
                'path' => $tables->path(),
                'per_page' => $tables->perPage(),
                'to' => $tables->lastItem(),
                'total' => $tables->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/tables/summary',
        operationId: 'tableSummary',
        summary: 'Ringkasan status meja',
        description: 'Mengambil ringkasan jumlah total meja, meja terisi, meja tersedia, dan isu integritas data.',
        tags: ['Tables'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ringkasan status meja',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_tables', type: 'integer', example: 10),
                        new OA\Property(property: 'occupied_tables', type: 'integer', example: 3),
                        new OA\Property(property: 'available_tables', type: 'integer', example: 7),
                        new OA\Property(property: 'data_integrity_issues', type: 'integer', example: 0),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission view_tables'),
        ],
    )]
    public function summary(): JsonResponse
    {
        $tables = Table::query()
            ->where('is_active', true)
            ->with('activeOrders')
            ->orderBy('number')
            ->get();

        $occupied = $tables->filter(
            fn (Table $table): bool => $table->activeOrders->isNotEmpty(),
        )->count();

        return response()->json([
            'total_tables' => $tables->count(),
            'occupied_tables' => $occupied,
            'available_tables' => $tables->count() - $occupied,
            'data_integrity_issues' => $tables->filter(
                fn (Table $table): bool => $table->activeOrders->count() > 1,
            )->count(),
        ]);
    }

    private function applyStatusFilter(Builder $query, string $status): Builder
    {
        return match ($status) {
            Table::STATUS_PROCESSING => $query->whereHas(
                'activeOrders',
                fn (Builder $orderQuery): Builder => $orderQuery->whereIn('status', Table::PROCESSING_ORDER_STATUSES),
            ),
            Table::STATUS_OCCUPIED => $query
                ->whereHas('activeOrders')
                ->whereDoesntHave(
                    'activeOrders',
                    fn (Builder $orderQuery): Builder => $orderQuery->whereIn('status', Table::PROCESSING_ORDER_STATUSES),
                ),
            Table::STATUS_AVAILABLE => $query->whereDoesntHave('activeOrders'),
        };
    }

    private function transformTable(Table $table): array
    {
        $order = $table->activeOrder();
        $customerName = filled($order?->customer_name) ? $order->customer_name : null;

        return [
            'id' => $table->id,
            'number' => $table->number,
            'capacity' => $table->capacity,
            'status' => $table->status,
            'display_status' => $customerName === null ? Table::STATUS_AVAILABLE : Table::STATUS_OCCUPIED,
            'customer_name' => $customerName,
            'order' => $order === null ? null : [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'order_type' => $order->order_type,
                'order_status' => $order->status,
            ],
            'active_orders_count' => $table->activeOrders->count(),
            'has_data_integrity_issue' => $table->has_data_integrity_issue,
        ];
    }
}
