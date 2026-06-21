<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_sku_id' => $this->product_sku_id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'product_price' => (float) $this->product_price,
            'quantity' => $this->quantity,
            'subtotal' => (float) $this->subtotal,
            'spec_snapshot' => $this->spec_snapshot,
            'spec_text' => $this->formatSpecText($this->spec_snapshot),
        ];
    }

    private function formatSpecText($specSnapshot): string
    {
        if (empty($specSnapshot)) {
            return '';
        }
        $parts = [];
        foreach ($specSnapshot as $key => $value) {
            $parts[] = "{$key}: {$value}";
        }
        return implode('; ', $parts);
    }
}
