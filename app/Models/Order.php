<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['order_number', 'runner_id', 'chef_id', 'table_id', 'customer_name', 'order_type', 'status', 'cancellation_reason', 'subtotal', 'discount', 'tax', 'total', 'completed_at'])]
class Order extends Model
{
    use HasFactory;

    public function runner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'runner_id');
    }

    public function chef(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chef_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount' => 'integer',
            'tax' => 'integer',
            'total' => 'integer',
            'completed_at' => 'datetime',
        ];
    }
}
