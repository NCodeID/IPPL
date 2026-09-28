<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::where('is_active', true)->paginate(10));
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }
}
