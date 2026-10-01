<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Midtrans\Config;
use Midtrans\Snap;

class PaymentService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function processPayment(Order $order, array $data, string $cashierId): Payment
    {
        if (! in_array($data['type'], ['cash', 'qris'], true)) {
            throw ValidationException::withMessages([
                'type' => ['Unsupported payment type.'],
            ])->status(422);
        }

        return DB::transaction(function () use ($order, $data, $cashierId): Payment {
            $order = $this->lockUnpaidOrder($order);

            if ($data['type'] === 'cash') {
                return $this->processCashPayment($order, $data, $cashierId);
            }

            return $this->processQrisPayment($order, $data, $cashierId);
        });
    }

    private function lockUnpaidOrder(Order $order): Order
    {
        $order = Order::lockForUpdate()->findOrFail($order->id);

        if ($order->payment_status !== 'unpaid') {
            throw ValidationException::withMessages([
                'order' => ['Pesanan ini sudah dibayar.'],
            ])->status(400);
        }

        return $order;
    }

    private function processCashPayment(Order $order, array $data, string $cashierId): Payment
    {
        $total = (int) round((float) $order->total);
        $tendered = (int) round((float) $data['tendered_amount']);

        if ($tendered < $total) {
            throw ValidationException::withMessages([
                'tendered_amount' => ['Uang yang dibayarkan kurang dari total pesanan.'],
            ])->status(422);
        }

        $changeAmount = $tendered - $total;

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method_id' => $data['payment_method_id'],
            'cashier_id' => $cashierId,
            'amount' => $total,
            'tendered_amount' => $tendered,
            'change_amount' => $changeAmount,
            'status' => 'success',
        ]);

        $order->update([
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        if ($order->order_type === 'dine_in') {
            $table = Table::findOrFail($order->table_id);
            $table->update(['status' => Table::STATUS_AVAILABLE]);
        }

        return $payment;
    }

    private function processQrisPayment(Order $order, array $data, string $cashierId): Payment
    {
        $total = (int) round((float) $order->total);

        $snapToken = Snap::getSnapToken([
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => $total,
            ],
            'customer_details' => [
                'name' => $order->customer_name,
            ],
        ]);

        return Payment::create([
            'order_id' => $order->id,
            'payment_method_id' => $data['payment_method_id'],
            'cashier_id' => $cashierId,
            'amount' => $total,
            'tendered_amount' => $total,
            'change_amount' => 0,
            'status' => 'pending',
            'snap_token' => $snapToken,
            'payment_reference' => (string) Str::uuid(),
        ]);
    }

    public function handleMidtransNotification(array $payload): bool
    {
        if (! $this->verifySignature($payload)) {
            throw ValidationException::withMessages([
                'signature' => ['Invalid signature.'],
            ])->status(403);
        }

        return DB::transaction(function () use ($payload): bool {
            $transactionStatus = $payload['transaction_status'];
            $orderNumber = $payload['order_num'];

            $order = Order::where('order_number', $orderNumber)->lockForUpdate()->first();

            if (! $order) {
                throw ValidationException::withMessages([
                    'order' => ['Order not found for this notification.'],
                ])->status(404);
            }

            if ($order->payment_status === 'paid') {
                return true;
            }

            $payment = Payment::where('order_id', $order->id)
                ->where('status', 'pending')
                ->first();

            if (! $payment) {
                return true;
            }

            if (in_array($transactionStatus, ['capture', 'settlement'], true)) {
                $payment->update([
                    'status' => 'success',
                    'paid_at' => now(),
                ]);

                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'completed',
                ]);

                if ($order->order_type === 'dine_in') {
                    $table = Table::findOrFail($order->table_id);
                    $table->update(['status' => Table::STATUS_AVAILABLE]);
                }
            }

            if (in_array($transactionStatus, ['cancel', 'deny', 'expire'], true)) {
                $payment->update(['status' => 'failed']);
            }

            return true;
        });
    }

    private function verifySignature(array $payload): bool
    {
        $orderId = $payload['order_id'] ?? $payload['order_num'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $serverKey = config('midtrans.server_key');
        $signatureKey = $payload['signature_key'] ?? '';

        if (! $orderId || ! $statusCode || ! $grossAmount || ! $serverKey || ! $signatureKey) {
            return false;
        }

        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expectedSignature, $signatureKey);
    }
}
