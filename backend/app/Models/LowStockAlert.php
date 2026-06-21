<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LowStockAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'current_stock',
        'threshold',
        'status',
        'read_at',
        'read_by',
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'threshold' => 'integer',
        'read_at' => 'datetime',
    ];

    /**
     * 商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 读取人
     */
    public function reader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'read_by');
    }

    /**
     * 标记为已读
     */
    public function markAsRead(?int $userId = null): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
            'read_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * 检查是否已读
     */
    public function isRead(): bool
    {
        return $this->status === 'read';
    }

    /**
     * 检查是否未读
     */
    public function isUnread(): bool
    {
        return $this->status === 'unread';
    }
}
