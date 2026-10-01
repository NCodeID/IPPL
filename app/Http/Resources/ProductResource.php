<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canSeeCost = $user && in_array($user->role, ['admin', 'akuntan', 'gudang'], true);

        $stock = $this->stock;
        if ($this->recipes->isNotEmpty()) {
            $portions = [];
            foreach ($this->recipes as $recipe) {
                $ingredient = $recipe->ingredient;
                if ($ingredient && $recipe->quantity_required > 0) {
                    $portions[] = (int) floor($ingredient->stock / $recipe->quantity_required);
                }
            }
            $stock = $portions ? min($portions) : 0;
        }

        $data = [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'description' => $this->description,
            'stock' => $stock,
            'image_url' => $this->image_url,
            'is_sellable' => $this->is_sellable,
            'is_available' => $this->is_available,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'total_requested' => $this->whenNotNull($this->total_requested),
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];

        if ($this->is_sellable || $canSeeCost) {
            $data['price'] = $this->price;
        } else {
            $data['price'] = null;
        }

        return $data;
    }
}
