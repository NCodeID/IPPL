<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function store(ProcessPaymentRequest $request, Order $order, PaymentService $paymentService): JsonResponse
    {
        $payment = $paymentService->processPayment($order, $request->validated(), $request->user()->id);

        return (new PaymentResource($payment))->response()->setStatusCode(201);
    }
}
