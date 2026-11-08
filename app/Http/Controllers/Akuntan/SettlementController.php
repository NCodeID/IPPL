<?php

namespace App\Http\Controllers\Akuntan;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class SettlementController extends Controller
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    #[OA\Get(
        path: '/api/settlements',
        operationId: 'settlementIndex',
        summary: 'Daftar settlement pengadaan',
        description: 'Menampilkan purchase request yang siap di-settlement (pending_settlement) dan yang telah selesai (completed), dengan metrik ringkasan dan filter.',
        tags: ['Settlements'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'status', required: false, schema: new OA\Schema(type: 'string', enum: ['all', 'pending_settlement', 'completed'])),
            new OA\QueryParameter(name: 'search', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'page', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\QueryParameter(name: 'per_page', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar settlement dan metrik'),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission'),
            new OA\Response(response: 422, description: 'Parameter query tidak valid'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:all,pending_settlement,completed'],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        $now = now();

        $pendingSettlementCount = PurchaseRequest::where('status', 'pending_settlement')->count();
        $completedThisMonth = PurchaseRequest::where('status', 'completed')
            ->whereYear('updated_at', $now->year)
            ->whereMonth('updated_at', $now->month)
            ->count();

        $completedWithDifference = PurchaseRequest::where('status', 'completed')
            ->whereYear('updated_at', $now->year)
            ->whereMonth('updated_at', $now->month)
            ->get()
            ->filter(function (PurchaseRequest $pr) {
                $pr->loadMissing('items');
                $estimated = (int) $pr->items->sum('estimated_subtotal');
                $actual = (int) $pr->items->sum('actual_subtotal');

                return $estimated !== $actual;
            })
            ->count();

        $totalActualThisMonth = (int) PurchaseRequestItem::query()
            ->whereHas('purchaseRequest', fn ($q) => $q->where('status', 'completed')
                ->whereYear('updated_at', $now->year)
                ->whereMonth('updated_at', $now->month)
            )
            ->sum('actual_subtotal');

        $activePrCount = PurchaseRequest::whereIn('status', ['pending_settlement', 'completed'])
            ->whereYear('updated_at', $now->year)
            ->whereMonth('updated_at', $now->month)
            ->count();

        $query = PurchaseRequest::query()
            ->whereIn('status', ['pending_settlement', 'completed'])
            ->with('user:id,name,role_id', 'user.role:id,name')
            ->withSum('items as estimated_total', 'estimated_subtotal')
            ->withSum('items as actual_total', 'actual_subtotal');

        $statusFilter = $validated['status'] ?? 'all';
        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if (filled($validated['search'] ?? null)) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $settlements = $query
            ->latest('updated_at')
            ->latest('id')
            ->paginate(perPage: (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE));

        return response()->json([
            'summary' => [
                'pending_settlement_count' => $pendingSettlementCount,
                'completed_this_month' => $completedThisMonth,
                'with_difference_count' => $completedWithDifference,
                'total_actual_this_month' => $totalActualThisMonth,
                'active_pr_count' => $activePrCount,
            ],
            'data' => $settlements->getCollection()
                ->map(function (PurchaseRequest $pr): array {
                    $estimated = (int) ($pr->estimated_total ?? 0);
                    $actual = (int) ($pr->actual_total ?? 0);
                    $difference = $estimated - $actual;

                    return [
                        'id' => $pr->id,
                        'request_number' => $pr->request_number,
                        'requester' => $pr->user === null ? null : [
                            'id' => $pr->user->id,
                            'name' => $pr->user->name,
                            'role' => $pr->user->role?->name,
                        ],
                        'status' => $pr->status,
                        'estimated_total' => $estimated,
                        'actual_total' => $actual,
                        'difference' => $difference,
                        'difference_label' => $difference > 0 ? 'Lebih' : ($difference < 0 ? 'Kurang' : 'Sesuai'),
                        'created_at' => $pr->created_at?->toISOString(),
                        'updated_at' => $pr->updated_at?->toISOString(),
                    ];
                })
                ->all(),
            'links' => [
                'first' => $settlements->url(1),
                'last' => $settlements->url($settlements->lastPage()),
                'prev' => $settlements->previousPageUrl(),
                'next' => $settlements->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $settlements->currentPage(),
                'from' => $settlements->firstItem(),
                'last_page' => $settlements->lastPage(),
                'path' => $settlements->path(),
                'per_page' => $settlements->perPage(),
                'to' => $settlements->lastItem(),
                'total' => $settlements->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/settlements/{purchaseRequest}',
        operationId: 'settlementShow',
        summary: 'Detail settlement pengadaan',
        description: 'Menampilkan detail lengkap settlement termasuk rekonsiliasi keuangan, rincian item aktual vs estimasi, dan informasi verifikasi.',
        tags: ['Settlements'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'purchaseRequest', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail settlement'),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission'),
            new OA\Response(response: 404, description: 'Settlement tidak ditemukan'),
        ],
    )]
    public function show(PurchaseRequest $purchaseRequest): JsonResponse
    {
        if (! in_array($purchaseRequest->status, ['pending_settlement', 'completed'])) {
            return response()->json(['message' => 'Purchase request belum tersedia untuk settlement.'], 404);
        }

        $purchaseRequest->load([
            'user:id,name,role_id',
            'user.role:id,name',
            'approver:id,name,role_id',
            'approver.role:id,name',
            'items.ingredient:id,name,unit_id',
            'items.ingredient.unit:id,code,name',
        ]);

        $estimatedTotal = (int) $purchaseRequest->items->sum('estimated_subtotal');
        $actualTotal = (int) $purchaseRequest->items->sum('actual_subtotal');
        $difference = $estimatedTotal - $actualTotal;

        $items = $purchaseRequest->items->map(fn (PurchaseRequestItem $item): array => [
            'id' => $item->id,
            'ingredient' => $item->ingredient === null ? null : [
                'id' => $item->ingredient->id,
                'name' => $item->ingredient->name,
                'unit' => $item->ingredient->unit?->name,
                'unit_code' => $item->ingredient->unit?->code,
            ],
            'estimated_quantity' => (float) $item->estimated_quantity,
            'estimated_price' => (int) $item->estimated_price,
            'estimated_subtotal' => (int) $item->estimated_subtotal,
            'actual_quantity' => $item->actual_quantity !== null ? (float) $item->actual_quantity : null,
            'actual_price' => $item->actual_price !== null ? (int) $item->actual_price : null,
            'actual_subtotal' => $item->actual_subtotal !== null ? (int) $item->actual_subtotal : null,
        ])->all();

        return response()->json([
            'data' => [
                'id' => $purchaseRequest->id,
                'request_number' => $purchaseRequest->request_number,
                'status' => $purchaseRequest->status,
                'note' => $purchaseRequest->note,
                'requester' => $purchaseRequest->user === null ? null : [
                    'id' => $purchaseRequest->user->id,
                    'name' => $purchaseRequest->user->name,
                    'role' => $purchaseRequest->user->role?->name,
                ],
                'approver' => $purchaseRequest->approver === null ? null : [
                    'id' => $purchaseRequest->approver->id,
                    'name' => $purchaseRequest->approver->name,
                    'role' => $purchaseRequest->approver->role?->name,
                ],
                'reconciliation' => [
                    'estimated_total' => $estimatedTotal,
                    'actual_total' => $actualTotal,
                    'difference' => $difference,
                    'difference_label' => $difference > 0 ? 'Lebih' : ($difference < 0 ? 'Kurang' : 'Sesuai'),
                ],
                'items_count' => $purchaseRequest->items->count(),
                'items' => $items,
                'created_at' => $purchaseRequest->created_at?->toISOString(),
                'approved_at' => $purchaseRequest->approved_at?->toISOString(),
                'updated_at' => $purchaseRequest->updated_at?->toISOString(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/settlements/{purchaseRequest}/complete',
        operationId: 'settlementComplete',
        summary: 'Selesaikan dan sahkan settlement',
        description: 'Mengubah status dari pending_settlement menjadi completed dan mencatat transaksi kas.',
        tags: ['Settlements'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'purchaseRequest', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'note', type: 'string', nullable: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Settlement berhasil disahkan'),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission'),
            new OA\Response(response: 404, description: 'Settlement tidak ditemukan'),
            new OA\Response(response: 422, description: 'Status bukan pending_settlement'),
        ],
    )]
    public function complete(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        if ($purchaseRequest->status !== 'pending_settlement') {
            return response()->json([
                'message' => 'Hanya settlement berstatus menunggu yang dapat diselesaikan.',
            ], 422);
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $purchaseRequest->load('items');
        $actualTotal = (int) $purchaseRequest->items->sum('actual_subtotal');

        DB::transaction(function () use ($purchaseRequest, $request, $validated, $actualTotal) {
            $purchaseRequest->update([
                'status' => 'completed',
                'note' => $validated['note'] ?? $purchaseRequest->note,
            ]);

            CashTransaction::create([
                'purchase_request_id' => $purchaseRequest->id,
                'recorded_by' => $request->user()->id,
                'amount' => $actualTotal,
                'description' => "Settlement PR {$purchaseRequest->request_number}",
                'transacted_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Settlement berhasil disahkan.',
            'data' => [
                'id' => $purchaseRequest->id,
                'request_number' => $purchaseRequest->request_number,
                'status' => $purchaseRequest->status,
                'actual_total' => $actualTotal,
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/settlements/{purchaseRequest}/revision',
        operationId: 'settlementRequestRevision',
        summary: 'Minta revisi nota settlement',
        description: 'Meminta petugas gudang merevisi nota belanja. Status tetap pending_settlement.',
        tags: ['Settlements'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'purchaseRequest', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['note'],
                properties: [
                    new OA\Property(property: 'note', type: 'string', description: 'Catatan revisi'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Permintaan revisi berhasil'),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission'),
            new OA\Response(response: 404, description: 'Settlement tidak ditemukan'),
            new OA\Response(response: 422, description: 'Status bukan pending_settlement atau validasi gagal'),
        ],
    )]
    public function requestRevision(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        if ($purchaseRequest->status !== 'pending_settlement') {
            return response()->json([
                'message' => 'Hanya settlement berstatus menunggu yang dapat diminta revisi.',
            ], 422);
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $purchaseRequest->update([
            'note' => $validated['note'],
        ]);

        return response()->json([
            'message' => 'Permintaan revisi nota berhasil dikirim.',
            'data' => [
                'id' => $purchaseRequest->id,
                'request_number' => $purchaseRequest->request_number,
                'status' => $purchaseRequest->status,
                'note' => $purchaseRequest->note,
            ],
        ]);
    }
}
