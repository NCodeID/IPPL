<?php

namespace App\Http\Controllers\Akuntan;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/*
|
| Nama Fitur : Purchase Request
| Desc : Purchase Request Controller
| Param:
|   page     (opsional) nomor halaman
|   per_page (opsional) jumlah data per halaman (default 15, max 100)
|
*/

class PurchaseRequestController extends Controller
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    #[OA\Get(
        path: '/api/purchase-requests',
        operationId: 'purchaseRequestIndex',
        summary: 'Daftar purchase request yang menunggu persetujuan',
        description: 'Menampilkan purchase request berstatus pending, diurutkan dari yang paling lama. Total dihitung dari jumlah estimated_subtotal seluruh item.',
        tags: ['Purchase Requests'],
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
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar purchase request pending',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'request_number', type: 'string', example: 'PR-20261106-0001'),
                                    new OA\Property(
                                        property: 'requester',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer'),
                                            new OA\Property(property: 'name', type: 'string'),
                                        ],
                                        type: 'object',
                                        nullable: true,
                                    ),
                                    new OA\Property(property: 'status', type: 'string', example: 'pending'),
                                    new OA\Property(property: 'note', type: 'string', nullable: true),
                                    new OA\Property(property: 'items_count', type: 'integer', example: 3),
                                    new OA\Property(property: 'total', type: 'integer', example: 250000),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object',
                            ),
                        ),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission manage_purchase_requests atau akun tidak aktif'),
            new OA\Response(response: 422, description: 'Parameter query tidak valid'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        $purchaseRequests = PurchaseRequest::query()
            ->where('status', 'pending')
            ->with('user:id,name')
            ->withCount('items')
            ->withSum('items as total', 'estimated_subtotal')
            ->oldest()
            ->oldest('id')
            ->paginate(perPage: (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE));

        return response()->json([
            'data' => $purchaseRequests->getCollection()
                ->map(fn (PurchaseRequest $purchaseRequest): array => [
                    'id' => $purchaseRequest->id,
                    'request_number' => $purchaseRequest->request_number,
                    'requester' => $purchaseRequest->user === null ? null : [
                        'id' => $purchaseRequest->user->id,
                        'name' => $purchaseRequest->user->name,
                    ],
                    'status' => $purchaseRequest->status,
                    'note' => $purchaseRequest->note,
                    'items_count' => (int) $purchaseRequest->items_count,
                    'total' => (int) ($purchaseRequest->total ?? 0),
                    'created_at' => $purchaseRequest->created_at?->toISOString(),
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
}
