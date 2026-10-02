<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $settlement = null;
        if ($this->status === self::STATUS_PENDING_SETTLEMENT) {
            $diff = $this->total_estimated_cost - $this->total_actual_cost;
            if ($diff > 0) {
                $settlement = ['type' => 'refund', 'amount' => $diff, 'message' => 'Terima Kembalian'];
            } elseif ($diff < 0) {
                $settlement = ['type' => 'reimbursement', 'amount' => abs($diff), 'message' => 'Bayar Reimburse'];
            }
        }

        return [
            'id' => $this->id,
            'request_number' => $this->request_number,
            'user_id' => $this->user_id,
            'approved_by' => $this->approved_by,
            'status' => $this->status,
            'note' => $this->note,
            'total_estimated_cost' => $this->total_estimated_cost,
            'total_actual_cost' => $this->total_actual_cost,
            'created_at' => $this->created_at,
            'approved_at' => $this->approved_at,
            'items' => PurchaseRequestItemResource::collection($this->whenLoaded('items')),
            'settlement' => $settlement,
        ];
    }
}
