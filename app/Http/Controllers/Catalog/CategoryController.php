<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    #[OA\Get(
        path: '/api/categories',
        operationId: 'categoryIndex',
        summary: 'Menampilkan kategori aktif',
        description: 'Mengambil daftar kategori aktif untuk filter menu pada halaman pembuatan order.',
        tags: ['Catalog'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar kategori aktif',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'name', type: 'string'),
                                ],
                                type: 'object',
                            ),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission manage_orders atau akun tidak aktif'),
        ],
    )]
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])
                ->all(),
        ]);
    }
}
