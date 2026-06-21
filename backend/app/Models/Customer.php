<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'order_count',
        'total_spent',
    ];

    protected $casts = [
        'order_count' => 'integer',
        'total_spent' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function updateStats(): void
    {
        $this->order_count = $this->orders()->count();
        $this->total_spent = $this->orders()->sum('final_amount') ?: 0;
        $this->save();
    }
}
