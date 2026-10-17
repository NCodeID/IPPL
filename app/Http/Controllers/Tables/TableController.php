<?php

namespace App\Http\Controllers\Tables;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TableController extends Controller
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

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

        return [
            'id' => $table->id,
            'number' => $table->number,
            'status' => $table->status,
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
