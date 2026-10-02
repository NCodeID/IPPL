<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Product;
use App\Models\ProductRecipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
class RecipeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return RecipeResource::collection(
            ProductRecipe::query()->with(['product', 'ingredient'])->orderBy('created_at', 'desc')->paginate(10)
        );
    }

    public function show(ProductRecipe $recipe): RecipeResource
    {
        return new RecipeResource($recipe->load(['product', 'ingredient']));
    }

    public function store(StoreRecipeRequest $request): JsonResponse
    {
        $product = Product::findOrFail($request->product_id);

        if ($product->is_sellable === false) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk ini bukan menu yang dijual. Resep hanya untuk produk yang dijual (is_sellable=true).',
            ], 422);
        }

        if ($product->recipes()->where('ingredient_id', $request->ingredient_id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bahan ini sudah terdaftar dalam resep produk ini.',
            ], 422);
        }

        $recipe = ProductRecipe::create([
            'product_id' => $request->product_id,
            'ingredient_id' => $request->ingredient_id,
            'quantity_required' => $request->quantity_required,
        ]);

        return (new RecipeResource($recipe->load(['product', 'ingredient'])))->response()->setStatusCode(201);
    }

    public function update(UpdateRecipeRequest $request, ProductRecipe $recipe): RecipeResource|JsonResponse
    {
        if ($recipe->product->is_sellable === false) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk ini bukan menu yang dijual.',
            ], 422);
        }

        if ($recipe->ingredient_id !== $request->ingredient_id && $recipe->product->recipes()->where('ingredient_id', $request->ingredient_id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bahan ini sudah terdaftar dalam resep produk ini.',
            ], 422);
        }

        $recipe->update([
            'ingredient_id' => $request->ingredient_id,
            'quantity_required' => $request->quantity_required,
        ]);

        return new RecipeResource($recipe->load(['product', 'ingredient']));
    }

    public function destroy(ProductRecipe $recipe): JsonResponse
    {
        $recipe->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Resep berhasil dihapus.',
        ]);
    }
}
