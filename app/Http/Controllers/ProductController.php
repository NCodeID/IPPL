<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Product Management
 */
class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $user = auth()->user();
        $query = Product::query()->with(['category', 'recipes.ingredient']);

        if ($user->role === 'admin') {
            $query->orderBy('created_at', 'desc');
        } elseif (in_array($user->role, ['kasir', 'runner'])) {
            $query->where('is_sellable', true)
                ->orderByRaw('CASE WHEN stock > 0 THEN 1 ELSE 2 END ASC')
                ->orderBy('name', 'asc');
        } elseif ($user->role === 'gudang') {
            $query->where('is_sellable', false)
                ->orderByRaw('CASE WHEN stock > 0 THEN 1 ELSE 2 END ASC')
                ->orderBy('name', 'asc');
        } elseif ($user->role === 'akuntan') {
            $query->where('is_sellable', false)
                ->whereHas('purchaseRequestItems.purchaseRequest', function ($q) {
                    $q->where('status', 'pending');
                })
                ->withSum(['purchaseRequestItems as total_requested' => function ($q) {
                    $q->whereHas('purchaseRequest', function ($q2) {
                        $q2->where('status', 'pending');
                    });
                }], 'quantity');
        }

        return ProductResource::collection($query->paginate(10));
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'recipes.ingredient']));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return new ProductResource($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->orderItems()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak dapat dihapus karena sudah digunakan dalam pesanan.',
            ], 422);
        }

        if ($product->purchaseRequestItems()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak dapat dihapus karena sudah digunakan dalam permintaan pembelian.',
            ], 422);
        }

        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil dihapus.',
        ]);
    }
}
