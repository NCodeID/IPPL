<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseRequestService
{
    public function createRequest(array $itemData, int $userId): PurchaseRequest
    {
        return DB::transaction(function () use ($itemData, $userId): PurchaseRequest {
            $productIds = array_column($itemData, 'product_id');
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            foreach ($itemData as $item) {
                $product = $products[$item['product_id']] ?? null;

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => ['Produk tidak ditemukan.'],
                    ])->status(422);
                }

                if ($product->is_sellable) {
                    throw ValidationException::withMessages([
                        'items' => ['Produk siap jual tidak dapat dibeli melalui modul bahan baku.'],
                    ])->status(422);
                }
            }

            $totalEstimatedCost = 0;

            foreach ($itemData as $item) {
                $totalEstimatedCost += $item['estimated_quantity'] * $item['estimated_price'];
            }

            $purchaseRequest = PurchaseRequest::create([
                'request_number' => 'PR-'.date('YmdHis').'-'.rand(100, 999),
                'user_id' => $userId,
                'status' => 'pending',
                'total_estimated_cost' => $totalEstimatedCost,
            ]);

            foreach ($itemData as $item) {
                PurchaseRequestItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'product_id' => $item['product_id'],
                    'estimated_quantity' => $item['estimated_quantity'],
                    'estimated_price' => $item['estimated_price'],
                    'subtotal' => $item['estimated_quantity'] * $item['estimated_price'],
                ]);
            }

            return $purchaseRequest;
        });
    }

    public function reviewRequest(PurchaseRequest $pr, int $approverId, string $status, ?string $note): PurchaseRequest
    {
        return DB::transaction(function () use ($pr, $approverId, $status, $note): PurchaseRequest {
            if (! in_array($status, ['approved', 'rejected'], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Status tidak valid.'],
                ])->status(422);
            }

            $pr->update([
                'status' => $status,
                'approved_by' => $approverId,
                'approved_at' => now(),
                'note' => $note,
            ]);

            return $pr;
        });
    }

    public function receiveGoods(PurchaseRequest $pr, array $actualItems): PurchaseRequest
    {
        return DB::transaction(function () use ($pr, $actualItems): PurchaseRequest {
            $pr = PurchaseRequest::lockForUpdate()->findOrFail($pr->id);

            if ($pr->status !== 'approved') {
                throw ValidationException::withMessages([
                    'status' => ['Purchase request harus berstatus approved untuk menerima barang.'],
                ])->status(400);
            }

            $totalActualCost = 0;

            foreach ($actualItems as $actualItem) {
                $prItem = PurchaseRequestItem::findOrFail($actualItem['id']);

                $prItem->update([
                    'actual_quantity' => $actualItem['actual_quantity'],
                    'actual_price' => $actualItem['actual_price'],
                ]);

                $totalActualCost += $actualItem['actual_quantity'] * $actualItem['actual_price'];

                if ($actualItem['actual_quantity'] > 0) {
                    $product = Product::lockForUpdate()->findOrFail($prItem->product_id);
                    $product->increment('stock', $actualItem['actual_quantity']);
                    $product->update(['price' => $actualItem['actual_price']]);
                }
            }

            $newStatus = ($pr->total_estimated_cost === $totalActualCost) ? 'completed' : 'pending_settlement';

            $pr->update([
                'total_actual_cost' => $totalActualCost,
                'status' => $newStatus,
            ]);

            return $pr;
        });
    }

    public function settle(PurchaseRequest $pr): PurchaseRequest
    {
        return DB::transaction(function () use ($pr): PurchaseRequest {
            $pr = PurchaseRequest::lockForUpdate()->findOrFail($pr->id);

            if ($pr->status !== 'pending_settlement') {
                throw ValidationException::withMessages([
                    'status' => ['Purchase request tidak dalam status pending_settlement.'],
                ])->status(400);
            }

            $pr->update(['status' => 'completed']);

            return $pr;
        });
    }
}
