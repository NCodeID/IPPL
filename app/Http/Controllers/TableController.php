<?php

namespace App\Http\Controllers;

use App\Http\Resources\TableResource;
use App\Models\Table;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TableController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return TableResource::collection(Table::where('is_active', true)->orderBy('number')->paginate(10));
    }

    public function show(Table $table): TableResource
    {
        return new TableResource($table);
    }
}
