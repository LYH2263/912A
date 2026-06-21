<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'value' => (float) $this->value,
            'min_amount' => (float) $this->min_amount,
            'total_quantity' => $this->total_quantity,
            'used_quantity' => $this->used_quantity,
            'per_user_limit' => $this->per_user_limit,
            'starts_at' => $this->starts_at?->toDateTimeString(),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
