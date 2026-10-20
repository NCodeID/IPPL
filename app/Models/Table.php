<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Attributes as OA;

#[Fillable(['number', 'is_active'])]
#[OA\Schema(
    schema: 'Table',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'number', type: 'integer'),
        new OA\Property(property: 'status', type: 'string', enum: ['processing', 'occupied', 'available']),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class Table extends Model
{
    use HasFactory;

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_OCCUPIED = 'occupied';

    public const STATUS_AVAILABLE = 'available';

    public const PROCESSING_ORDER_STATUSES = [
        Order::STATUS_PENDING,
        Order::STATUS_COOKING,
        Order::STATUS_READY,
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    public function activeOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id')->active();
    }

    public function activeOrder(): ?Order
    {
        if ($this->activeOrders->isEmpty()) {
            return null;
        }

        $processing = $this->activeOrders
            ->whereIn('status', self::PROCESSING_ORDER_STATUSES)
            ->sortByDesc('id');

        if ($processing->isNotEmpty()) {
            return $processing->first();
        }

        return $this->activeOrders->sortByDesc('id')->first();
    }

    protected function status(): Attribute
    {
        return Attribute::get(fn (): string => $this->resolveStatus());
    }

    protected function hasDataIntegrityIssue(): Attribute
    {
        return Attribute::get(fn (): bool => $this->activeOrders->count() > 1);
    }

    private function resolveStatus(): string
    {
        $statuses = $this->activeOrders->pluck('status');

        if ($statuses->intersect(self::PROCESSING_ORDER_STATUSES)->isNotEmpty()) {
            return self::STATUS_PROCESSING;
        }

        if ($statuses->contains(Order::STATUS_SERVED)) {
            return self::STATUS_OCCUPIED;
        }

        return self::STATUS_AVAILABLE;
    }

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
