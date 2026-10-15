<?php

namespace App\Models;

use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['unit_id', 'name', 'unit_cost', 'stock', 'is_active'])]
class Ingredient extends Model
{
    use HasFactory;

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function productRecipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class);
    }

    protected function casts(): array
    {
        return [
            'unit_cost' => 'integer',
            'stock' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}