<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSku extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'cost_price',
        'stock_quantity',
        'image',
        'spec_data',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'spec_data' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function hasEnoughStock(int $quantity): bool
    {
        return $this->stock_quantity >= $quantity;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock_quantity === 0;
    }

    public function getSpecTextAttribute(): string
    {
        if (empty($this->spec_data)) {
            return '';
        }
        $parts = [];
        foreach ($this->spec_data as $key => $value) {
            $parts[] = "{$key}: {$value}";
        }
        return implode('; ', $parts);
    }
}
