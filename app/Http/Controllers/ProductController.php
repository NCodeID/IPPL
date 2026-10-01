<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
}
