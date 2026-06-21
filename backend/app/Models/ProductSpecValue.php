<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpecValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'spec_id',
        'value',
        'sort',
    ];

    protected $casts = [
        'sort' => 'integer',
    ];

    public function spec(): BelongsTo
    {
        return $this->belongsTo(ProductSpec::class, 'spec_id');
    }
}
