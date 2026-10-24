<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    #[OA\Get(
        path: '/api/products',
        operationId: 'productIndex',
        summary: 'Menampilkan daftar menu',
        description: 'Mengambil daftar produk aktif beserta status kelayakan order berdasarkan ketersediaan bahan baku.',
        tags: ['Catalog'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\QueryParameter(
                name: 'search',
                description: 'Pencarian berdasarkan nama atau deskripsi produk',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\QueryParameter(
                name: 'category_id',
                description: 'Filter berdasarkan ID kategori',
                required: false,
                schema: new OA\Schema(type: 'integer'),
            ),
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
                description: 'Daftar produk',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'description', type: 'string', nullable: true),
                                    new OA\Property(property: 'price', type: 'integer'),
                                    new OA\Property(property: 'image_url', type: 'string', nullable: true),
                                    new OA\Property(
                                        property: 'category',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', nullable: true),
                                            new OA\Property(property: 'name', type: 'string', nullable: true),
                                        ],
                                        type: 'object',
                                    ),
                                    new OA\Property(property: 'is_available', type: 'boolean'),
                                    new OA\Property(property: 'is_orderable', type: 'boolean'),
                                    new OA\Property(
                                        property: 'unavailable_reason',
                                        type: 'string',
                                        nullable: true,
                                        enum: [
                                            'product_inactive',
                                            'category_inactive',
                                            'not_available',
                                            'recipe_missing',
                                            'recipe_invalid',
                                            'ingredient_inactive',
                                            'insufficient_stock',
                                        ],
                                    ),
                                ],
                                type: 'object',
                            ),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Tidak terautentikasi'),
            new OA\Response(response: 403, description: 'Tidak memiliki permission manage_orders atau akun tidak aktif'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
        ]);

        $products = Product::query()
            ->with(['category', 'recipes.ingredient'])
            ->where('is_active', true)
            ->whereHas('category', fn (Builder $query): Builder => $query->where('is_active', true))
            ->when(
                $validated['search'] ?? null,
                fn (Builder $query, string $search): Builder => $query->where(
                    fn (Builder $searchQuery): Builder => $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%"),
                ),
            )
            ->when(
                $validated['category_id'] ?? null,
                fn (Builder $query, int|string $categoryId): Builder => $query->where('category_id', (int) $categoryId),
            )
            ->orderBy('name')
            ->paginate((int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE));

        return response()->json([
            'data' => $products->getCollection()
                ->map(fn (Product $product): array => $this->transformProduct($product))
                ->all(),
            'links' => [
                'first' => $products->url(1),
                'last' => $products->url($products->lastPage()),
                'prev' => $products->previousPageUrl(),
                'next' => $products->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $products->currentPage(),
                'from' => $products->firstItem(),
                'last_page' => $products->lastPage(),
                'links' => $products->linkCollection()->toArray(),
                'path' => $products->path(),
                'per_page' => $products->perPage(),
                'to' => $products->lastItem(),
                'total' => $products->total(),
            ],
        ]);
    }

    private function transformProduct(Product $product): array
    {
        $evaluation = $this->evaluateOrderability($product);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'image_url' => $product->image_url,
            'category' => [
                'id' => $product->category?->id,
                'name' => $product->category?->name,
            ],
            'is_available' => $product->is_available,
            'is_orderable' => $evaluation['is_orderable'],
            'unavailable_reason' => $evaluation['unavailable_reason'],
        ];
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

    private function unavailable(string $reason): array
    {
        return [
            'is_orderable' => false,
            'unavailable_reason' => $reason,
        ];
    }
}
