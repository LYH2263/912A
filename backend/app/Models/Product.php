<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'category_id',
        'description',
        'price',
        'cost_price',
        'image',
        'images',
        'status',
        'stock_quantity',
        'low_stock_threshold',
        'weight',
    ];

    protected $casts = [
        'images' => 'array',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    /**
     * 分类
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 订单项
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * 规格
     */
    public function specs(): HasMany
    {
        return $this->hasMany(ProductSpec::class)->orderBy('sort');
    }

    /**
     * SKU列表
     */
    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class);
    }

    /**
     * 库存变动记录
     */
    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    /**
     * SKU总数
     */
    public function getSkuCountAttribute(): int
    {
        if ($this->relationLoaded('skus')) {
            return $this->skus->count();
        }
        if (array_key_exists('sku_count', $this->attributes)) {
            return (int) $this->attributes['sku_count'];
        }
        return $this->skus()->count();
    }

    /**
     * 总库存
     */
    public function getTotalStockAttribute(): int
    {
        if (array_key_exists('total_stock', $this->attributes)) {
            return (int) $this->attributes['total_stock'];
        }
        return (int) $this->skus()->sum('stock_quantity');
    }

    /**
     * 最低价格
     */
    public function getMinPriceAttribute(): ?float
    {
        if (array_key_exists('min_price', $this->attributes)) {
            return $this->attributes['min_price'] ? (float) $this->attributes['min_price'] : null;
        }
        $sku = $this->skus()->orderBy('price', 'asc')->first();
        return $sku ? (float) $sku->price : null;
    }

    /**
     * 最高价格
     */
    public function getMaxPriceAttribute(): ?float
    {
        if (array_key_exists('max_price', $this->attributes)) {
            return $this->attributes['max_price'] ? (float) $this->attributes['max_price'] : null;
        }
        $sku = $this->skus()->orderBy('price', 'desc')->first();
        return $sku ? (float) $sku->price : null;
    }

    /**
     * 是否有规格
     */
    public function getHasSpecsAttribute(): bool
    {
        if ($this->relationLoaded('specs')) {
            return $this->specs->count() > 0;
        }
        if (array_key_exists('sku_count', $this->attributes)) {
            return (int) $this->attributes['sku_count'] > 0;
        }
        return $this->specs()->count() > 0;
    }

    /**
     * 检查库存是否充足
     */
    public function hasEnoughStock(int $quantity): bool
    {
        return $this->stock_quantity >= $quantity;
    }

    /**
     * 检查是否低库存
     */
    public function isLowStock(): bool
    {
        return $this->stock_quantity > 0 && $this->stock_quantity <= $this->low_stock_threshold;
    }

    /**
     * 检查是否缺货
     */
    public function isOutOfStock(): bool
    {
        return $this->stock_quantity === 0;
    }
}
