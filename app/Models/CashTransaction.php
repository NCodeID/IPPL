<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cash_category_id', 'payment_id', 'purchase_request_id', 'recorded_by', 'amount', 'description', 'transacted_at'])]
class CashTransaction extends Model
{
    use HasFactory;

    public function cashCategory(): BelongsTo
    {
        return $this->belongsTo(CashCategory::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'transacted_at' => 'datetime',
        ];
    }
}
