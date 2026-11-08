<?php

namespace App\Models;

use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;
use SensitiveParameter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

#[Fillable(['number', 'capacity', 'is_active'])]
#[OA\Schema(
    schema: 'Table',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'number', type: 'integer'),
        new OA\Property(property: 'capacity', type: 'integer', minimum: 1, example: 4),
        new OA\Property(property: 'status', type: 'string', enum: ['processing', 'occupied', 'available']),
        new OA\Property(property: 'display_status', type: 'string', enum: ['available', 'occupied']),
        new OA\Property(property: 'customer_name', type: 'string', nullable: true),
        new OA\Property(
            property: 'order',
            type: 'object',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'order_number', type: 'string'),
                new OA\Property(property: 'customer_name', type: 'string'),
                new OA\Property(property: 'order_type', type: 'string'),
                new OA\Property(property: 'order_status', type: 'string'),
                new OA\Property(property: 'note', type: 'string', nullable: true),
            ],
        ),
        new OA\Property(property: 'active_orders_count', type: 'integer'),
        new OA\Property(property: 'has_data_integrity_issue', type: 'boolean'),
    ],
)]
class Table extends Model
{
    use HasFactory;

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_OCCUPIED = 'occupied';

    public const STATUS_AVAILABLE = 'available';

    public const HOLD_SECONDS = 600;

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

    /** @param list<int>|null $heldTableIds */
    public function scopeOccupied(Builder $query, ?array $heldTableIds = null): Builder
    {
        $heldTableIds ??= array_keys(self::holdsForTables((clone $query)->pluck('tables.id')->all()));

        return $query->where(fn (Builder $query): Builder => $query->whereHas('activeOrders')->orWhereIn('tables.id', $heldTableIds));
    }

    /** @param list<int>|null $heldTableIds */
    public function scopeAvailable(Builder $query, ?array $heldTableIds = null): Builder
    {
        $heldTableIds ??= array_keys(self::holdsForTables((clone $query)->pluck('tables.id')->all()));

        return $query->whereDoesntHave('activeOrders')->whereNotIn('tables.id', $heldTableIds);
    }

    /**
     * Mutation callers must already hold the Table row lock before requesting current reads.
     *
     * @param  list<int>  $tableIds
     * @return array<int, array{owner_id: int, token_hash: string, expires_at: int}>
     */
    public static function holdsForTables(array $tableIds, bool $lockForUpdate = false): array
    {
        if ($tableIds === []) {
            return [];
        }

        try {
            $store = self::holdCache()->getStore();
            $keys = [];
            foreach ($tableIds as $tableId) {
                $keys[$store->getPrefix().'table-hold:'.$tableId] = $tableId;
            }
            $now = now()->timestamp;
            $query = $store->getConnection()->table(config('cache.stores.database.table'))
                ->whereIn('key', array_keys($keys))->where('expiration', '>', $now);
            if ($lockForUpdate) {
                $query->lockForUpdate();
            }
            $rows = $query->get();
            $now = now()->timestamp;
            $holds = [];
            foreach ($rows as $row) {
                $hold = unserialize($row->value, ['allowed_classes' => false]);
                if (! is_array($hold) || count($hold) !== 3 || ! is_int($hold['owner_id'] ?? null)
                    || ! is_string($hold['token_hash'] ?? null) || ! preg_match('/^[a-f0-9]{64}$/', $hold['token_hash'])
                    || ! is_int($hold['expires_at'] ?? null)) {
                    throw new HttpException(503);
                }
                if (min($hold['expires_at'], (int) $row->expiration) > $now) {
                    $holds[$keys[$row->key]] = $hold;
                }
            }

            return $holds;
        } catch (Throwable $error) {
            self::holdStorageFailure($error);
        }
    }

    /** @return array{owner_id: int, token_hash: string, expires_at: int}|null */
    public function currentHold(): ?array
    {
        return self::holdsForTables([$this->getKey()], lockForUpdate: true)[$this->getKey()] ?? null;
    }

    /** @param array{owner_id: int, token_hash: string, expires_at: int}|null $hold */
    public function ownsHold(?array $hold, int $ownerId, #[SensitiveParameter] string $token): bool
    {
        return $hold !== null && $hold['owner_id'] === $ownerId && hash_equals($hold['token_hash'], hash('sha256', $token));
    }

    /** @param array{owner_id: int, token_hash: string, expires_at: int} $hold */
    public function storeHold(array $hold): void
    {
        try {
            if ($this->getConnection()->transactionLevel() === 0 || ! self::holdCache()->put('table-hold:'.$this->getKey(), $hold, self::HOLD_SECONDS)) {
                throw new HttpException(503);
            }
        } catch (Throwable $error) {
            self::holdStorageFailure($error);
        }
    }

    public function deleteHold(): void
    {
        try {
            if ($this->getConnection()->transactionLevel() === 0 || ! self::holdCache()->forget('table-hold:'.$this->getKey())) {
                throw new HttpException(503);
            }
        } catch (Throwable $error) {
            self::holdStorageFailure($error);
        }
    }

    private static function holdStorageFailure(Throwable $error): never
    {
        if ($error instanceof QueryException && in_array($error->errorInfo[1] ?? null, [1205, 1213], true)) {
            throw $error;
        }

        throw new HttpException(503, 'Penyimpanan hold meja tidak tersedia.');
    }

    private static function holdCache(): Repository
    {
        $cache = Cache::store('database');
        $store = $cache->getStore();
        $connection = (new self)->getConnection();
        if (! $store instanceof DatabaseStore || $store->getConnection() !== $connection || $connection->getDriverName() !== 'mysql') {
            throw new HttpException(503, 'Penyimpanan hold meja tidak tersedia.');
        }

        return $cache;
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
        return Attribute::get(fn (): string => $this->resolveStatus(isset(self::holdsForTables([$this->getKey()])[$this->getKey()])));
    }

    protected function hasDataIntegrityIssue(): Attribute
    {
        return Attribute::get(fn (): bool => $this->activeOrders->count() > 1);
    }

    public function resolveStatus(bool $held = false): string
    {
        $statuses = $this->activeOrders->pluck('status');

        if ($statuses->intersect(self::PROCESSING_ORDER_STATUSES)->isNotEmpty()) {
            return self::STATUS_PROCESSING;
        }

        if ($statuses->contains(Order::STATUS_SERVED) || $held) {
            return self::STATUS_OCCUPIED;
        }

        return self::STATUS_AVAILABLE;
    }

    protected static function booted(): void
    {
        static::saving(function (Table $table): void {
            if ($table->capacity === null || $table->capacity <= 0) {
                throw ValidationException::withMessages([
                    'capacity' => 'Kapasitas meja wajib diisi dan harus lebih besar dari nol.',
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
