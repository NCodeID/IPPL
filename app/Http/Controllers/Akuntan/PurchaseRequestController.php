<?php

namespace App\Http\Controllers\Akuntan;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class PurchaseRequestController extends Controller
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    #[OA\Get(
        path: '/api/purchase-requests',
        operationId: 'purchaseRequestIndex',
        summary: 'Daftar purchase request dengan filter dan pencarian',
        description: 'Menampilkan purchase request dengan filter status, pencarian, dan pagination. Menyertakan ringkasan metrik.',
        tags: ['Purchase Requests'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(name: 'status', required: false, schema: new OA\Schema(type: 'string', enum: ['all', 'pending', 'approved', 'rejected'])),
            new OA\QueryParameter(name: 'search', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'requester_id', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'page', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\QueryParameter(name: 'per_page', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar purchase request dan metrik ringkasan'),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission'),
            new OA\Response(response: 422, description: 'Parameter query tidak valid'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:all,pending,approved,rejected'],
            'search' => ['nullable', 'string', 'max:255'],
            'requester_id' => ['nullable', 'integer', 'exists:users,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        $now = now();

        $pendingCount = PurchaseRequest::where('status', 'pending')->count();
        $approvedThisMonth = PurchaseRequest::where('status', 'approved')
            ->whereYear('approved_at', $now->year)
            ->whereMonth('approved_at', $now->month)
            ->count();
        $rejectedThisMonth = PurchaseRequest::where('status', 'rejected')
            ->whereYear('approved_at', $now->year)
            ->whereMonth('approved_at', $now->month)
            ->count();
        $pendingEstimatedTotal = (int) PurchaseRequestItem::query()
            ->whereHas('purchaseRequest', fn ($q) => $q->where('status', 'pending'))
            ->sum('estimated_subtotal');

        $query = PurchaseRequest::query()
            ->with('user:id,name', 'user.role:id,name')
            ->withCount('items')
            ->withSum('items as estimated_total', 'estimated_subtotal');

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

        if (filled($validated['requester_id'] ?? null)) {
            $query->where('user_id', $validated['requester_id']);
        }

        $purchaseRequests = $query
            ->latest()
            ->latest('id')
            ->paginate(perPage: (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE));

        return response()->json([
            'summary' => [
                'pending_count' => $pendingCount,
                'approved_this_month' => $approvedThisMonth,
                'rejected_this_month' => $rejectedThisMonth,
                'pending_estimated_total' => $pendingEstimatedTotal,
            ],
            'data' => $purchaseRequests->getCollection()
                ->map(fn (PurchaseRequest $pr): array => [
                    'id' => $pr->id,
                    'request_number' => $pr->request_number,
                    'requester' => $pr->user === null ? null : [
                        'id' => $pr->user->id,
                        'name' => $pr->user->name,
                        'role' => $pr->user->role?->name,
                    ],
                    'status' => $pr->status,
                    'note' => $pr->note,
                    'items_count' => (int) $pr->items_count,
                    'estimated_total' => (int) ($pr->estimated_total ?? 0),
                    'created_at' => $pr->created_at?->toISOString(),
                    'approved_at' => $pr->approved_at?->toISOString(),
                ])
                ->all(),
            'links' => [
                'first' => $purchaseRequests->url(1),
                'last' => $purchaseRequests->url($purchaseRequests->lastPage()),
                'prev' => $purchaseRequests->previousPageUrl(),
                'next' => $purchaseRequests->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $purchaseRequests->currentPage(),
                'from' => $purchaseRequests->firstItem(),
                'last_page' => $purchaseRequests->lastPage(),
                'path' => $purchaseRequests->path(),
                'per_page' => $purchaseRequests->perPage(),
                'to' => $purchaseRequests->lastItem(),
                'total' => $purchaseRequests->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/purchase-requests/{purchaseRequest}',
        operationId: 'purchaseRequestShow',
        summary: 'Detail purchase request',
        description: 'Menampilkan detail lengkap purchase request beserta rincian item bahan baku, informasi pemohon, dan catatan akuntan.',
        tags: ['Purchase Requests'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'purchaseRequest', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail purchase request'),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission'),
            new OA\Response(response: 404, description: 'Purchase request tidak ditemukan'),
        ],
    )]
    public function show(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $purchaseRequest->load([
            'user:id,name,role_id',
            'user.role:id,name',
            'approver:id,name,role_id',
            'approver.role:id,name',
            'items.ingredient:id,name,unit_id',
            'items.ingredient.unit:id,code,name',
        ]);

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

        $estimatedTotal = (int) $purchaseRequest->items->sum('estimated_subtotal');
        $actualTotal = (int) $purchaseRequest->items->sum('actual_subtotal');

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
                'items_count' => $purchaseRequest->items->count(),
                'items' => $items,
                'estimated_total' => $estimatedTotal,
                'actual_total' => $actualTotal,
                'difference' => $estimatedTotal - $actualTotal,
                'created_at' => $purchaseRequest->created_at?->toISOString(),
                'approved_at' => $purchaseRequest->approved_at?->toISOString(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/purchase-requests/{purchaseRequest}/approve',
        operationId: 'purchaseRequestApprove',
        summary: 'Setujui purchase request',
        description: 'Mengubah status purchase request dari pending menjadi approved. Catatan akuntan bersifat opsional.',
        tags: ['Purchase Requests'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'purchaseRequest', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'note', type: 'string', nullable: true, description: 'Catatan akuntan (opsional)'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Purchase request berhasil disetujui'),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission'),
            new OA\Response(response: 404, description: 'Purchase request tidak ditemukan'),
            new OA\Response(response: 422, description: 'Status bukan pending atau validasi gagal'),
        ],
    )]
    public function approve(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        if ($purchaseRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Hanya purchase request berstatus pending yang dapat disetujui.',
            ], 422);
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($purchaseRequest, $request, $validated) {
            $purchaseRequest->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'note' => $validated['note'] ?? $purchaseRequest->note,
            ]);
        });

        $purchaseRequest->load('approver:id,name');

        return response()->json([
            'message' => 'Purchase request berhasil disetujui.',
            'data' => [
                'id' => $purchaseRequest->id,
                'request_number' => $purchaseRequest->request_number,
                'status' => $purchaseRequest->status,
                'approved_by' => $purchaseRequest->approver ? [
                    'id' => $purchaseRequest->approver->id,
                    'name' => $purchaseRequest->approver->name,
                ] : null,
                'approved_at' => $purchaseRequest->approved_at?->toISOString(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/purchase-requests/{purchaseRequest}/reject',
        operationId: 'purchaseRequestReject',
        summary: 'Tolak purchase request',
        description: 'Mengubah status purchase request dari pending menjadi rejected. Catatan akuntan wajib diisi sebagai alasan penolakan.',
        tags: ['Purchase Requests'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\PathParameter(name: 'purchaseRequest', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['note'],
                properties: [
                    new OA\Property(property: 'note', type: 'string', description: 'Alasan penolakan (wajib)'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Purchase request berhasil ditolak'),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission'),
            new OA\Response(response: 404, description: 'Purchase request tidak ditemukan'),
            new OA\Response(response: 422, description: 'Status bukan pending atau validasi gagal'),
        ],
    )]
    public function reject(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        if ($purchaseRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Hanya purchase request berstatus pending yang dapat ditolak.',
            ], 422);
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($purchaseRequest, $request, $validated) {
            $purchaseRequest->update([
                'status' => 'rejected',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'note' => $validated['note'],
            ]);
        });

        $purchaseRequest->load('approver:id,name');

        return response()->json([
            'message' => 'Purchase request berhasil ditolak.',
            'data' => [
                'id' => $purchaseRequest->id,
                'request_number' => $purchaseRequest->request_number,
                'status' => $purchaseRequest->status,
                'approved_by' => $purchaseRequest->approver ? [
                    'id' => $purchaseRequest->approver->id,
                    'name' => $purchaseRequest->approver->name,
                ] : null,
                'approved_at' => $purchaseRequest->approved_at?->toISOString(),
            ],
        ]);
    }
}
