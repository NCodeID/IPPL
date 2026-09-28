<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection(
            Product::where('is_active', true)
                ->where('is_available', true)
                ->where('is_sellable', true)
                ->with('category')
                ->paginate(10)
        );
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('category'));
    }
}
