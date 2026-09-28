<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentService $paymentService): JsonResponse
    {
        try {
            $paymentService->handleMidtransNotification($request->all());

            return response()->json(['message' => 'ok']);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], 500);
        }
    }
}
