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
        private ProductRepository $repository
    ) {
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $specs = $data['specs'] ?? [];
            $skus = $data['skus'] ?? [];
            unset($data['specs'], $data['skus']);

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

            return $this->repository->findWithSpecs($product->id);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $specs = $data['specs'] ?? null;
            $skus = $data['skus'] ?? null;
            unset($data['specs'], $data['skus']);

            if (isset($data['sku']) && $this->repository->existsBySku($data['sku'], $product->id)) {
                throw new \Exception('SKU 已存在，请使用其他 SKU');
            }

            if (isset($data['cost_price']) && isset($data['price']) && $data['cost_price'] > $data['price']) {
                throw new \Exception('成本价不能大于售价');
            }

            if (!empty($data)) {
                $product = $this->repository->update($product, $data);
            }

            if ($specs !== null) {
                $this->repository->saveSpecs($product, $specs);
            }

            if ($skus !== null) {
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
            }

            return $this->repository->findWithSpecs($product->id);
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
        return $this->repository->findWithSpecs($id);
    }
}
