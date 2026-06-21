<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku_id',
        'batch_no',
        'production_date',
        'shelf_life_days',
        'expiry_date',
        'quantity',
        'initial_quantity',
        'unit_cost',
        'status',
        'is_sellable',
        'remark',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'initial_quantity' => 'integer',
        'shelf_life_days' => 'integer',
        'unit_cost' => 'decimal:2',
        'production_date' => 'date',
        'expiry_date' => 'date',
        'is_sellable' => 'boolean',
    ];

    const STATUS_NORMAL = 'normal';
    const STATUS_EXPIRING_SOON = 'expiring_soon';
    const STATUS_EXPIRED = 'expired';

    const EXPIRING_SOON_DAYS = 30;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        return Carbon::now()->startOfDay()->diffInDays($this->expiry_date, false);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->days_until_expiry < 0;
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        $days = $this->days_until_expiry;
        return $days >= 0 && $days <= self::EXPIRING_SOON_DAYS;
    }

    public function getUsedQuantityAttribute(): int
    {
        return $this->initial_quantity - $this->quantity;
    }

    public function getUsageRateAttribute(): float
    {
        if ($this->initial_quantity === 0) {
            return 0;
        }
        return round(($this->used_quantity / $this->initial_quantity) * 100, 2);
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NORMAL => '正常',
            self::STATUS_EXPIRING_SOON => '临期',
            self::STATUS_EXPIRED => '已过期',
            default => $this->status,
        };
    }

    public function refreshStatus(): void
    {
        if ($this->is_expired) {
            $this->status = self::STATUS_EXPIRED;
            $this->is_sellable = false;
        } elseif ($this->is_expiring_soon) {
            $this->status = self::STATUS_EXPIRING_SOON;
            $this->is_sellable = true;
        } else {
            $this->status = self::STATUS_NORMAL;
            $this->is_sellable = true;
        }
    }

    public function hasEnoughStock(int $quantity): bool
    {
        return $this->is_sellable && $this->quantity >= $quantity;
    }

    public function isDepleted(): bool
    {
        return $this->quantity === 0;
    }

    protected static function booted(): void
    {
        static::creating(function (self $batch) {
            if (empty($batch->expiry_date) && !empty($batch->production_date) && !empty($batch->shelf_life_days)) {
                $batch->expiry_date = Carbon::parse($batch->production_date)->addDays($batch->shelf_life_days);
            }
            $batch->refreshStatus();
        });

        static::saving(function (self $batch) {
            if (!empty($batch->production_date) && !empty($batch->shelf_life_days)) {
                $calculated = Carbon::parse($batch->production_date)->addDays($batch->shelf_life_days);
                if (empty($batch->expiry_date) || $batch->expiry_date->toDateString() !== $calculated->toDateString()) {
                    $batch->expiry_date = $calculated;
                }
            }
            $batch->refreshStatus();
        });
    }
}
