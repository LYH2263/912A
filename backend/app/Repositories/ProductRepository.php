<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductSpec;
use App\Models\ProductSpecValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::with(['category', 'supplier', 'tags'])
            ->withCount('skus as sku_count')
            ->withMin('skus as min_price', 'price')
            ->withMax('skus as max_price', 'price')
            ->withSum('skus as total_stock', 'stock_quantity');

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['stock_status'])) {
            $stockStatus = $filters['stock_status'];
            if ($stockStatus === 'out_of_stock') {
                $query->where('stock_quantity', 0);
            } elseif ($stockStatus === 'low_stock') {
                $query->whereRaw('stock_quantity > 0 AND stock_quantity <= low_stock_threshold');
            } elseif ($stockStatus === 'in_stock') {
                $query->whereRaw('stock_quantity > low_stock_threshold');
            }
        }

        if (isset($filters['tag_ids']) && !empty($filters['tag_ids'])) {
            $tagIds = is_array($filters['tag_ids']) ? $filters['tag_ids'] : explode(',', $filters['tag_ids']);
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            }, '=', count($tagIds));
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('sku', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function all(array $filters = []): Collection
    {
        $query = Product::with('category');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    public function find(int $id): ?Product
    {
        return Product::with(['category', 'supplier'])->find($id);
    }

    public function findWithSpecs(int $id): ?Product
    {
        return Product::with(['category', 'supplier', 'specs.values', 'skus'])->find($id);
    }

    public function existsBySku(string $sku, ?int $excludeId = null): bool
    {
        $query = Product::where('sku', $sku);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function saveSpecs(Product $product, array $specs): void
    {
        DB::transaction(function () use ($product, $specs) {
            $product->specs()->delete();

            foreach ($specs as $specIndex => $spec) {
                $productSpec = $product->specs()->create([
                    'name' => $spec['name'],
                    'sort' => $specIndex,
                ]);

                foreach ($spec['values'] as $valueIndex => $value) {
                    $productSpec->values()->create([
                        'value' => $value,
                        'sort' => $valueIndex,
                    ]);
                }
            }
        });
    }

    public function saveSkus(Product $product, array $skus): void
    {
        DB::transaction(function () use ($product, $skus) {
            $existingSkuIds = $product->skus->pluck('id')->toArray();

            foreach ($skus as $skuData) {
                if (isset($skuData['id']) && in_array($skuData['id'], $existingSkuIds)) {
                    $sku = ProductSku::find($skuData['id']);
                    $sku->update([
                        'sku' => $skuData['sku'],
                        'price' => $skuData['price'],
                        'cost_price' => $skuData['cost_price'] ?? null,
                        'stock_quantity' => $skuData['stock_quantity'] ?? 0,
                        'image' => $skuData['image'] ?? null,
                        'spec_data' => $skuData['spec_data'] ?? null,
                        'status' => $skuData['status'] ?? 'active',
                    ]);
                } else {
                    $product->skus()->create([
                        'sku' => $skuData['sku'],
                        'price' => $skuData['price'],
                        'cost_price' => $skuData['cost_price'] ?? null,
                        'stock_quantity' => $skuData['stock_quantity'] ?? 0,
                        'image' => $skuData['image'] ?? null,
                        'spec_data' => $skuData['spec_data'] ?? null,
                        'status' => $skuData['status'] ?? 'active',
                    ]);
                }
            }

            $newSkuIds = collect($skus)->pluck('id')->filter()->toArray();
            $toDeleteIds = array_diff($existingSkuIds, $newSkuIds);
            if (!empty($toDeleteIds)) {
                ProductSku::whereIn('id', $toDeleteIds)->delete();
            }

            $product->refresh();
            $totalStock = $product->skus->sum('stock_quantity');
            $minPrice = $product->skus->min('price');
            $product->update([
                'stock_quantity' => $totalStock,
                'price' => $minPrice ?? $product->price,
            ]);

            if ($totalStock === 0 && $product->status === 'active') {
                $product->update(['status' => 'sold_out']);
            } elseif ($totalStock > 0 && $product->status === 'sold_out') {
                $product->update(['status' => 'active']);
            }
        });
    }

    public function findSkuById(int $skuId): ?ProductSku
    {
        return ProductSku::find($skuId);
    }

    public function decreaseSkuStock(ProductSku $sku, int $quantity): void
    {
        $sku->decrement('stock_quantity', $quantity);
    }

    public function increaseSkuStock(ProductSku $sku, int $quantity): void
    {
        $sku->increment('stock_quantity', $quantity);
    }

    public function syncTags(Product $product, array $tagIds): void
    {
        $product->tags()->sync($tagIds);
    }

    public function attachTags(array $productIds, array $tagIds): int
    {
        $count = 0;
        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            if ($product) {
                $product->tags()->syncWithoutDetaching($tagIds);
                $count++;
            }
        }
        return $count;
    }

    public function detachTags(array $productIds, array $tagIds): int
    {
        $count = 0;
        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            if ($product) {
                $product->tags()->detach($tagIds);
                $count++;
            }
        }
        return $count;
    }

    public function findWithTags(int $id): ?Product
    {
        return Product::with(['category', 'supplier', 'specs.values', 'skus', 'tags'])->find($id);
    }
}
