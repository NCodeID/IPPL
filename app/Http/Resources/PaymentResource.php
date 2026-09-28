<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'payment_method_id' => $this->payment_method_id,
            'cashier_id' => $this->cashier_id,
            'amount' => $this->amount,
            'tendered_amount' => $this->tendered_amount,
            'change_amount' => $this->change_amount,
            'status' => $this->status,
            'snap_token' => $this->whenNotNull($this->snap_token),
            'payment_reference' => $this->whenNotNull($this->payment_reference),
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
