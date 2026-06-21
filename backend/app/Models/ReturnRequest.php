<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequest extends Model
{
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'return_no',
        'order_id',
        'order_item_id',
        'type',
        'reason',
        'refund_amount',
        'quantity',
        'status',
        'reject_reason',
        'approved_at',
        'rejected_at',
        'completed_at',
        'operator_id',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'quantity' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public static function generateReturnNo(): string
    {
        return 'RETURN' . date('Ymd') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}
