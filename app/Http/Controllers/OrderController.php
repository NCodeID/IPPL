<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Table;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Order::query()
            ->with(['table', 'runner', 'chef', 'orderItems.product', 'payments'])
            ->orderByDesc('created_at');

        $user = auth()->user();

        if ($user->role === 'kasir') {
            $query->whereNotIn('status', ['completed', 'cancelled']);
        } elseif ($user->role === 'runner') {
            $query->whereIn('status', ['cooking', 'ready']);
        } elseif ($user->role === 'dapur') {
            $query->whereIn('status', ['pending', 'cooking']);
        }

        if ($request->filled('status')) {
            $query->whereIn('status', array_map('trim', explode(',', $request->input('status'))));
        }

        if ($request->filled('payment_status')) {
            $query->whereIn('payment_status', array_map('trim', explode(',', $request->input('payment_status'))));
        }

        return OrderResource::collection($query->paginate(10));
    }

    public function history(): AnonymousResourceCollection
    {
        $orders = Order::with(['table', 'orderItems.product', 'payments'])
            ->latest()
            ->limit(3)
            ->get();

        return OrderResource::collection($orders);
    }

    public function show(Order $order): OrderResource
    {
        $order->load(['orderItems', 'table', 'chef', 'runner']);

        return new OrderResource($order);
    }

    public function store(StoreOrderRequest $request, OrderService $orderService): JsonResponse
    {
        $order = $orderService->createOrder($request->validated(), $request->user()->id);

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, OrderService $orderService): OrderResource
    {
        $user = auth()->user();
        $requestedStatus = $request->input('status');

        if ($user->role === 'dapur' && ! in_array($requestedStatus, ['cooking', 'ready'], true)) {
            abort(403, 'Dapur hanya dapat mengubah status menjadi cooking atau ready.');
        }

        if ($user->role === 'runner' && $requestedStatus !== 'served') {
            abort(403, 'Runner hanya dapat mengubah status menjadi served.');
        }

        $status = $request->validated()['status'];

        $order = $orderService->updateStatus($order, $status, $request->user()->id);

        return new OrderResource($order);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();

        if ($order->payment_status === 'paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pesanan lunas tidak dapat dibatalkan.',
            ], 400);
        }

        if (in_array($order->status, ['cancelled', 'completed'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Status pesanan tidak valid.',
            ], 400);
        }

        if ($user->role === 'runner' && $order->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Dapur sudah memproses pesanan ini. Hubungi Admin untuk membatalkan.',
            ], 403);
        }

        $cancellationReason = $request->input('cancellation_reason');

        DB::transaction(function () use ($order, $cancellationReason): void {
            $order->load('orderItems.product.recipes.ingredient');

            foreach ($order->orderItems as $orderItem) {
                $product = $orderItem->product;

                if ($product->recipes->isNotEmpty()) {
                    foreach ($product->recipes as $recipe) {
                        $ingredient = Product::findOrFail($recipe->ingredient_id);
                        $restoreQuantity = $recipe->quantity_required * $orderItem->quantity;
                        $ingredient->increment('stock', $restoreQuantity);
                    }
                } else {
                    $product->increment('stock', $orderItem->quantity);
                }
            }

            if ($order->table_id) {
                $hasOtherActiveOrders = Order::query()
                    ->where('table_id', $order->table_id)
                    ->where('id', '!=', $order->id)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->exists();

                if (! $hasOtherActiveOrders) {
                    Table::where('id', $order->table_id)
                        ->update(['status' => Table::STATUS_AVAILABLE]);
                }
            }

            $order->update([
                'status' => 'cancelled',
                'cancellation_reason' => $cancellationReason,
            ]);
        });

        $order->refresh()->load(['orderItems', 'table', 'chef', 'runner']);

        return response()->json([
            'status' => 'success',
            'message' => 'Pesanan berhasil dibatalkan.',
            'data' => new OrderResource($order),
        ]);
    }
}
