<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku_id' => $this->sku_id,
            'sku' => $this->whenLoaded('sku', function () {
                return $this->sku ? [
                    'id' => $this->sku->id,
                    'sku' => $this->sku->sku,
                    'spec_text' => $this->sku->spec_text,
                ] : null;
            }),
            'old_price' => (float) $this->old_price,
            'new_price' => (float) $this->new_price,
            'price_change' => round((float) $this->new_price - (float) $this->old_price, 2),
            'price_change_percent' => $this->old_price > 0
                ? round(((float) $this->new_price - (float) $this->old_price) / (float) $this->old_price * 100, 2)
                : 0,
            'reason' => $this->reason,
            'operator_id' => $this->operator_id,
            'operator' => $this->whenLoaded('operator', function () {
                return $this->operator ? [
                    'id' => $this->operator->id,
                    'name' => $this->operator->name,
                ] : null;
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
