<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

    public function store(StoreOrderRequest $request, OrderService $orderService): JsonResponse
    {
        $order = $orderService->createOrder($request->validated(), $request->user()->id);

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, OrderService $orderService): OrderResource
    {
        $status = $request->validated()['status'];

        $order = $orderService->updateStatus($order, $status, $request->user()->id);

        return new OrderResource($order);
    }
}
