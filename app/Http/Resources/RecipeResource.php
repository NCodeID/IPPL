<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product->name),
            'ingredient_id' => $this->ingredient_id,
            'ingredient_name' => $this->whenLoaded('ingredient', fn () => $this->ingredient->name),
            'quantity_required' => $this->quantity_required,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
