<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['purchase_request_id', 'ingredient_id', 'estimated_quantity', 'estimated_price', 'actual_quantity', 'actual_price', 'estimated_subtotal', 'actual_subtotal'])]
class PurchaseRequestItem extends Model
{
    use HasFactory;

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    protected function casts(): array
    {
        return [
            'estimated_quantity' => 'decimal:2',
            'estimated_price' => 'integer',
            'actual_quantity' => 'decimal:2',
            'actual_price' => 'integer',
            'estimated_subtotal' => 'integer',
            'actual_subtotal' => 'integer',
        ];
    }
}
