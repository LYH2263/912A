<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'value',
        'min_amount',
        'total_quantity',
        'used_quantity',
        'per_user_limit',
        'starts_at',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'total_quantity' => 'integer',
        'used_quantity' => 'integer',
        'per_user_limit' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coupon_user')
            ->withPivot('times_used')
            ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeValid($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->whereColumn('used_quantity', '<', 'total_quantity')
            ->where('total_quantity', '>', 0);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isNotStarted(): bool
    {
        return $this->starts_at && $this->starts_at->isFuture();
    }

    public function isExhausted(): bool
    {
        return $this->total_quantity > 0 && $this->used_quantity >= $this->total_quantity;
    }

    public function canBeUsedBy(?User $user): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->isExpired() || $this->isNotStarted()) {
            return false;
        }

        if ($this->isExhausted()) {
            return false;
        }

        if ($user) {
            $pivot = $this->users()->where('user_id', $user->id)->first();
            if ($pivot && $pivot->pivot->times_used >= $this->per_user_limit) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount(float $orderAmount): float
    {
        if ($orderAmount < $this->min_amount) {
            return 0;
        }

        if ($this->type === 'fixed') {
            return min((float) $this->value, $orderAmount);
        }

        if ($this->type === 'percent') {
            $discount = $orderAmount * ((float) $this->value / 100);
            return round(min($discount, $orderAmount), 2);
        }

        return 0;
    }
}
