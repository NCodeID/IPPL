<?php

namespace App\Http\Controllers\Tables;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class TableController extends Controller
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    #[OA\Get(
        path: '/api/tables',
        operationId: 'tableIndex',
        summary: 'Menampilkan daftar meja',
        description: 'Mengambil daftar meja aktif beserta order aktif-nya. Hold yang belum kedaluwarsa ikut menentukan okupansi, filter, dan total pagination. Token dan pemilik hold tidak ditampilkan.',
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
                description: 'processing: order pending/cooking/ready; occupied: order aktif atau hold valid; available: tanpa order aktif dan tanpa hold valid',
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
            new OA\Response(response: 503, description: 'Penyimpanan hold tidak tersedia'),
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

        return DB::transaction(function () use ($validated): JsonResponse {
            $baseQuery = Table::query()
                ->where('is_active', true)
                ->when(
                    $validated['search'] ?? null,
                    fn (Builder $query, string $search): Builder => $query->where('number', 'like', "%{$search}%"),
                );
            $holds = Table::holdsForTables((clone $baseQuery)->pluck('id')->all());
            $tables = $baseQuery
                ->when(
                    $validated['status'] ?? null,
                    fn (Builder $query, string $status): Builder => $this->applyStatusFilter($query, $status, array_keys($holds)),
                )
                ->with('activeOrders')
                ->orderBy('number')
                ->paginate(perPage: (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE));

            return response()->json([
                'data' => $tables->getCollection()
                    ->map(fn (Table $table): array => $this->transformTable($table, isset($holds[$table->id])))
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
        });
    }

    #[OA\Get(
        path: '/api/tables/{table}',
        operationId: 'tableShow',
        summary: 'Menampilkan detail meja',
        description: 'Mengambil detail satu meja aktif beserta order aktif-nya. Meja dengan hold saja memiliki status/display_status occupied, order/customer_name null, dan active_orders_count 0.',
        tags: ['Tables'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(
                name: 'table',
                description: 'ID meja',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail meja',
                content: new OA\JsonContent(ref: '#/components/schemas/Table'),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission view_tables'),
            new OA\Response(response: 404, description: 'Meja tidak ditemukan atau tidak aktif'),
            new OA\Response(response: 503, description: 'Penyimpanan hold tidak tersedia'),
        ],
    )]
    public function show(Table $table): JsonResponse
    {
        return DB::transaction(function () use ($table): JsonResponse {
            $table = Table::query()->whereKey($table->getKey())->where('is_active', true)->firstOrFail();
            $holds = Table::holdsForTables([$table->id]);
            $table->load('activeOrders');

            return response()->json($this->transformTable($table, isset($holds[$table->id])));
        });
    }

    #[OA\Get(
        path: '/api/tables/summary',
        operationId: 'tableSummary',
        summary: 'Ringkasan status meja',
        description: 'Menghitung meja aktif. Meja terisi memiliki order aktif atau hold valid dan dihitung satu kali. Hold tidak menambah jumlah order atau isu integritas order.',
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
            new OA\Response(response: 503, description: 'Penyimpanan hold tidak tersedia'),
        ],
    )]
    public function summary(): JsonResponse
    {
        return DB::transaction(function (): JsonResponse {
            $tables = Table::query()->where('is_active', true);
            $holds = Table::holdsForTables((clone $tables)->pluck('id')->all());

            $total = (clone $tables)->count();
            $occupied = (clone $tables)->occupied(array_keys($holds))->count();

            return response()->json([
                'total_tables' => $total,
                'occupied_tables' => $occupied,
                'available_tables' => $total - $occupied,
                'data_integrity_issues' => (clone $tables)->has('activeOrders', '>', 1)->count(),
            ]);
        });
    }

    #[OA\Post(
        path: '/api/tables/{table}/hold',
        operationId: 'tableHoldAcquire',
        summary: 'Runner dan kasir mengambil hold meja dine-in',
        description: 'Memerlukan akun aktif, role runner atau kasir, view_tables dan manage_orders. Tidak menyimpan draft. Hold berlaku 10 menit; frontend memperbarui setiap 2 menit. POST ulang pada hold yang masih valid ditolak, termasuk untuk pemilik yang sama.',
        tags: ['Tables'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'table', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 201, description: 'Hold dibuat; token hanya dikembalikan kepada pemilik', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'hold_token', type: 'string', minLength: 64, maxLength: 64),
                    new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
                ]),
            ])),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Role, status akun, atau permission tidak sesuai'),
            new OA\Response(response: 404, description: 'Meja tidak ditemukan'),
            new OA\Response(response: 409, description: 'Meja tidak aktif, memiliki order aktif, atau sedang di-hold'),
            new OA\Response(response: 503, description: 'Penyimpanan hold tidak tersedia; operasi ditolak'),
        ],
    )]
    #[OA\Patch(
        path: '/api/tables/{table}/hold',
        operationId: 'tableHoldRenew',
        summary: 'Pemilik memperpanjang hold meja',
        description: 'Role/permission sama dengan acquire. Hanya pemilik dan token instance yang cocok dapat memperpanjang hold yang belum kedaluwarsa menjadi 10 menit dari waktu server.',
        tags: ['Tables'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'table', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['hold_token'], properties: [
            new OA\Property(property: 'hold_token', type: 'string', format: 'password', minLength: 64, maxLength: 64),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Hold diperpanjang', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'object', properties: [new OA\Property(property: 'expires_at', type: 'string', format: 'date-time')]),
            ])),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Role, status akun, atau permission tidak sesuai'),
            new OA\Response(response: 404, description: 'Meja tidak ditemukan'),
            new OA\Response(response: 409, description: 'Token/pemilik tidak cocok, hold hilang/kedaluwarsa, atau meja tidak tersedia'),
            new OA\Response(response: 422, description: 'hold_token wajib berupa string 64 karakter'),
            new OA\Response(response: 503, description: 'Penyimpanan hold tidak tersedia; operasi ditolak'),
        ],
    )]
    #[OA\Delete(
        path: '/api/tables/{table}/hold',
        operationId: 'tableHoldRelease',
        summary: 'Pemilik melepas hold saat batal atau pindah meja',
        description: 'Role/permission sama dengan acquire. Memerlukan token instance yang cocok dan hold yang masih valid. Tidak menghapus order atau membebaskan okupansi dari order aktif.',
        tags: ['Tables'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'table', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['hold_token'], properties: [
            new OA\Property(property: 'hold_token', type: 'string', format: 'password', minLength: 64, maxLength: 64),
        ])),
        responses: [
            new OA\Response(response: 204, description: 'Hold dilepas, tanpa response body'),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Role, status akun, atau permission tidak sesuai'),
            new OA\Response(response: 404, description: 'Meja tidak ditemukan'),
            new OA\Response(response: 409, description: 'Token/pemilik tidak cocok atau hold hilang/kedaluwarsa'),
            new OA\Response(response: 422, description: 'hold_token wajib berupa string 64 karakter'),
            new OA\Response(response: 503, description: 'Penyimpanan hold tidak tersedia; operasi ditolak'),
        ],
    )]
    public function hold(Request $request, Table $table): Response
    {
        $validated = $request->isMethod('POST') ? [] : $request->validate([
            'hold_token' => ['required', 'string', 'size:64'],
        ]);

        return DB::transaction(function () use ($request, $table, $validated): Response {
            $table = Table::query()->whereKey($table->getKey())->lockForUpdate()->firstOrFail();
            $hold = $table->currentHold();

            if ($request->isMethod('POST')) {
                abort_if(! $table->is_active || $table->activeOrders()->lockForUpdate()->exists() || $hold !== null, 409, 'Meja tidak tersedia untuk hold.');
                $token = bin2hex(random_bytes(32));
                $hold = ['owner_id' => $request->user()->id, 'token_hash' => hash('sha256', $token), 'expires_at' => now()->timestamp + Table::HOLD_SECONDS];
                $table->storeHold($hold);

                return response()->json(['data' => ['hold_token' => $token, 'expires_at' => Carbon::createFromTimestampUTC($hold['expires_at'])->toISOString()]], 201);
            }

            abort_unless($table->ownsHold($hold, $request->user()->id, $validated['hold_token']), 409, 'Hold meja tidak valid atau sudah berakhir.');
            if ($request->isMethod('DELETE')) {
                $table->deleteHold();

                return response()->noContent();
            }

            abort_if(! $table->is_active || $table->activeOrders()->lockForUpdate()->exists(), 409, 'Meja tidak tersedia untuk hold.');
            $renewed = [...$hold, 'expires_at' => now()->timestamp + Table::HOLD_SECONDS];
            if ($renewed !== $hold) {
                $table->storeHold($renewed);
            }

            return response()->json(['data' => ['expires_at' => Carbon::createFromTimestampUTC($renewed['expires_at'])->toISOString()]]);
        }, attempts: 3);
    }

    /** @param list<int> $heldTableIds */
    private function applyStatusFilter(Builder $query, string $status, array $heldTableIds): Builder
    {
        return match ($status) {
            Table::STATUS_PROCESSING => $query->whereHas(
                'activeOrders',
                fn (Builder $orderQuery): Builder => $orderQuery->whereIn('status', Table::PROCESSING_ORDER_STATUSES),
            ),
            Table::STATUS_OCCUPIED => $query->occupied($heldTableIds),
            Table::STATUS_AVAILABLE => $query->available($heldTableIds),
        };
    }

    private function transformTable(Table $table, bool $held): array
    {
        $order = $table->activeOrder();
        $customerName = filled($order?->customer_name) ? $order->customer_name : null;

        return [
            'id' => $table->id,
            'number' => $table->number,
            'capacity' => $table->capacity,
            'status' => $table->resolveStatus($held),
            'display_status' => $order === null && ! $held ? Table::STATUS_AVAILABLE : Table::STATUS_OCCUPIED,
            'customer_name' => $customerName,
            'order' => $order === null ? null : [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'order_type' => $order->order_type,
                'order_status' => $order->status,
                'note' => $order->note,
            ],
            'active_orders_count' => $table->activeOrders->count(),
            'has_data_integrity_issue' => $table->has_data_integrity_issue,
        ];
    }
}
