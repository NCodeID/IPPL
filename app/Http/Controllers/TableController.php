<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTableRequest;
use App\Http\Requests\UpdateTableRequest;
use App\Http\Resources\TableResource;
use App\Models\Order;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
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

    public function store(StoreTableRequest $request): JsonResponse
    {
        $table = Table::create($request->validated());

        return (new TableResource($table))->response()->setStatusCode(201);
    }

    public function update(UpdateTableRequest $request, Table $table): TableResource
    {
        $table->update($request->validated());

        return new TableResource($table);
    }

    public function destroy(Table $table): JsonResponse
    {
        $hasActiveOrders = Order::where('table_id', $table->id)
            ->whereIn('status', ['pending', 'cooking', 'ready', 'served'])
            ->exists();

        if ($hasActiveOrders) {
            return response()->json([
                'status' => 'error',
                'message' => 'Meja tidak dapat dihapus karena sedang digunakan oleh pesanan aktif.',
            ], 422);
        }

        $table->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Meja berhasil dihapus.',
        ]);
    }
}
