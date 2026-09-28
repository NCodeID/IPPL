<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'runner_id' => $this->runner_id,
            'runner' => new UserResource($this->whenLoaded('runner')),
            'chef_id' => $this->chef_id,
            'chef' => new UserResource($this->whenLoaded('chef')),
            'table_id' => $this->table_id,
            'table' => new TableResource($this->whenLoaded('table')),
            'customer_name' => $this->customer_name,
            'order_type' => $this->order_type,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'note' => $this->note,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
            'created_at' => $this->created_at,
            'completed_at' => $this->completed_at,
            'updated_at' => $this->updated_at,
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
