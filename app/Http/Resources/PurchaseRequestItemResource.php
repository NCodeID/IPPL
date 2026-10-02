<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product->name),
            'estimated_quantity' => $this->estimated_quantity,
            'estimated_price' => $this->estimated_price,
            'actual_quantity' => $this->actual_quantity,
            'actual_price' => $this->actual_price,
        ];
    }
}
