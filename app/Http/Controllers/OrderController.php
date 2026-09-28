<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, OrderService $orderService): JsonResponse
    {
        $order = $orderService->createOrder($request->validated(), $request->user()->id);

        return (new OrderResource($order))->response()->setStatusCode(201);
    }
}
