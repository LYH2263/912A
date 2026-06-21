<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'description' => $this->description,
            'price' => (float) $this->price,
            'cost_price' => $this->cost_price ? (float) $this->cost_price : null,
            'image' => $this->image,
            'images' => $this->images,
            'status' => $this->status,
            'stock_quantity' => $this->stock_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'weight' => $this->weight ? (float) $this->weight : null,
            'sku_count' => $this->sku_count,
            'total_stock' => $this->total_stock,
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'has_specs' => $this->has_specs,
            'specs' => $this->whenLoaded('specs', function () {
                return $this->specs->map(function ($spec) {
                    return [
                        'id' => $spec->id,
                        'name' => $spec->name,
                        'sort' => $spec->sort,
                        'values' => $spec->values->map(function ($value) {
                            return [
                                'id' => $value->id,
                                'value' => $value->value,
                                'sort' => $value->sort,
                            ];
                        }),
                    ];
                });
            }),
            'skus' => $this->whenLoaded('skus', function () {
                return $this->skus->map(function ($sku) {
                    return [
                        'id' => $sku->id,
                        'sku' => $sku->sku,
                        'price' => (float) $sku->price,
                        'cost_price' => $sku->cost_price ? (float) $sku->cost_price : null,
                        'stock_quantity' => $sku->stock_quantity,
                        'image' => $sku->image,
                        'spec_data' => $sku->spec_data,
                        'spec_text' => $sku->spec_text,
                        'status' => $sku->status,
                    ];
                });
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
