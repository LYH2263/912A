<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSku;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public function __construct(
        private ProductRepository $repository,
        private PriceHistoryService $priceHistoryService
    ) {
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $specs = $data['specs'] ?? [];
            $skus = $data['skus'] ?? [];
            $tagIds = $data['tag_ids'] ?? [];
            unset($data['specs'], $data['skus'], $data['tag_ids']);

            if (empty($skus)) {
                if ($this->repository->existsBySku($data['sku'])) {
                    throw new \Exception('SKU 已存在，请使用其他 SKU');
                }
            }

            if (isset($data['cost_price']) && $data['cost_price'] > $data['price']) {
                throw new \Exception('成本价不能大于售价');
            }

            $product = $this->repository->create($data);

            if (!empty($specs)) {
                $this->repository->saveSpecs($product, $specs);
            }

            if (!empty($skus)) {
                foreach ($skus as $sku) {
                    if (ProductSku::where('sku', $sku['sku'])->exists()) {
                        throw new \Exception("SKU {$sku['sku']} 已存在，请使用其他 SKU");
                    }
                }
                $this->repository->saveSkus($product, $skus);
            }

            if (!empty($tagIds)) {
                $this->repository->syncTags($product, $tagIds);
            }

            return $this->repository->findWithTags($product->id);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $specs = $data['specs'] ?? null;
            $skus = $data['skus'] ?? null;
            $tagIds = $data['tag_ids'] ?? null;
            $priceReason = $data['price_reason'] ?? null;
            unset($data['specs'], $data['skus'], $data['tag_ids'], $data['price_reason']);

            if (isset($data['sku']) && $this->repository->existsBySku($data['sku'], $product->id)) {
                throw new \Exception('SKU 已存在，请使用其他 SKU');
            }

            if (isset($data['cost_price']) && isset($data['price']) && $data['cost_price'] > $data['price']) {
                throw new \Exception('成本价不能大于售价');
            }

            $oldProductPrice = $product->price;
            $oldSkuPrices = [];
            if ($skus !== null) {
                foreach ($product->skus as $sku) {
                    $oldSkuPrices[$sku->id] = $sku->price;
                }
            }

            if (!empty($data)) {
                $product = $this->repository->update($product, $data);
            }

            if (isset($data['price']) && abs($oldProductPrice - $data['price']) > 0.0001) {
                $this->priceHistoryService->recordPriceChange(
                    $product,
                    (float) $oldProductPrice,
                    (float) $data['price'],
                    $priceReason
                );
            }

            if ($specs !== null) {
                if (empty($specs)) {
                    $this->repository->clearSpecsAndSkus($product);
                } else {
                    $this->repository->saveSpecs($product, $specs);
                }
            }

            if ($skus !== null) {
                if (empty($skus)) {
                    $this->repository->clearSpecsAndSkus($product);
                } else {
                    foreach ($skus as $sku) {
                        if (empty($sku['id']) && ProductSku::where('sku', $sku['sku'])->exists()) {
                            throw new \Exception("SKU {$sku['sku']} 已存在，请使用其他 SKU");
                        }
                        if (!empty($sku['id'])) {
                            $existing = ProductSku::where('sku', $sku['sku'])
                                ->where('id', '!=', $sku['id'])
                                ->exists();
                            if ($existing) {
                                throw new \Exception("SKU {$sku['sku']} 已存在，请使用其他 SKU");
                            }
                        }
                    }
                    $this->repository->saveSkus($product, $skus);

                    $product->refresh();
                    foreach ($product->skus as $sku) {
                        if (isset($oldSkuPrices[$sku->id]) && abs($oldSkuPrices[$sku->id] - $sku->price) > 0.0001) {
                            $this->priceHistoryService->recordPriceChange(
                                $product,
                                (float) $oldSkuPrices[$sku->id],
                                (float) $sku->price,
                                $priceReason,
                                $sku
                            );
                        }
                    }

                    $newProductPrice = $product->price;
                    if (abs($oldProductPrice - $newProductPrice) > 0.0001 && !isset($data['price'])) {
                        $this->priceHistoryService->recordPriceChange(
                            $product,
                            (float) $oldProductPrice,
                            (float) $newProductPrice,
                            $priceReason ?: 'SKU价格变动导致商品均价调整'
                        );
                    }
                }
            }

            if ($tagIds !== null) {
                $this->repository->syncTags($product, $tagIds);
            }

            return $this->repository->findWithTags($product->id);
        });
    }

    public function delete(Product $product): bool
    {
        if ($product->orderItems()->exists()) {
            return $product->delete();
        }

        return $product->forceDelete();
    }

    public function toggleStatus(Product $product): Product
    {
        $status = $product->status === 'active' ? 'inactive' : 'active';
        return $this->repository->update($product, ['status' => $status]);
    }

    public function getProductWithSpecs(int $id): ?Product
    {
        return $this->repository->findWithTags($id);
    }
}
