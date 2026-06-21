<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public function __construct(
        private ProductRepository $repository
    ) {
    }

    /**
     * 创建商品
     */
    public function create(array $data): Product
    {
        // 验证 SKU 唯一性
        if ($this->repository->existsBySku($data['sku'])) {
            throw new \Exception('SKU 已存在，请使用其他 SKU');
        }

        // 验证价格
        if (isset($data['cost_price']) && $data['cost_price'] > $data['price']) {
            throw new \Exception('成本价不能大于售价');
        }

        return $this->repository->create($data);
    }

    /**
     * 更新商品
     */
    public function update(Product $product, array $data): Product
    {
        // 验证 SKU 唯一性（排除自身）
        if (isset($data['sku']) && $this->repository->existsBySku($data['sku'], $product->id)) {
            throw new \Exception('SKU 已存在，请使用其他 SKU');
        }

        // 验证价格
        if (isset($data['cost_price']) && isset($data['price']) && $data['cost_price'] > $data['price']) {
            throw new \Exception('成本价不能大于售价');
        }

        return $this->repository->update($product, $data);
    }

    /**
     * 删除商品
     */
    public function delete(Product $product): bool
    {
        // 检查是否有订单关联
        if ($product->orderItems()->exists()) {
            // 使用软删除
            return $product->delete();
        }

        // 物理删除
        return $product->forceDelete();
    }

    /**
     * 切换商品状态
     */
    public function toggleStatus(Product $product): Product
    {
        $status = $product->status === 'active' ? 'inactive' : 'active';
        return $this->repository->update($product, ['status' => $status]);
    }
}
