<?php

namespace App\Services;

use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * 增加库存（入库）
     */
    public function increaseStock(Product $product, int $quantity, ?int $orderId = null, string $remark = ''): InventoryLog
    {
        return DB::transaction(function () use ($product, $quantity, $orderId, $remark) {
            $beforeQuantity = $product->stock_quantity;
            $afterQuantity = $beforeQuantity + $quantity;

            $product->update(['stock_quantity' => $afterQuantity]);

            $log = InventoryLog::create([
                'product_id' => $product->id,
                'type' => $orderId ? 'return' : 'in',
                'quantity' => $quantity,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $afterQuantity,
                'related_order_id' => $orderId,
                'remark' => $remark,
                'operator_id' => auth()->id(),
            ]);

            Log::info('库存增加', [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'before' => $beforeQuantity,
                'after' => $afterQuantity,
            ]);

            return $log;
        });
    }

    /**
     * 减少库存（出库/销售）
     */
    public function decreaseStock(Product $product, int $quantity, ?int $orderId = null, string $remark = ''): InventoryLog
    {
        return DB::transaction(function () use ($product, $quantity, $orderId, $remark) {
            if ($product->stock_quantity < $quantity) {
                throw new \Exception("库存不足，当前库存：{$product->stock_quantity}");
            }

            $beforeQuantity = $product->stock_quantity;
            $afterQuantity = $beforeQuantity - $quantity;

            $product->update(['stock_quantity' => $afterQuantity]);

            // 更新商品状态
            if ($afterQuantity === 0) {
                $product->update(['status' => 'sold_out']);
            }

            $log = InventoryLog::create([
                'product_id' => $product->id,
                'type' => $orderId ? 'sale' : 'out',
                'quantity' => -$quantity,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $afterQuantity,
                'related_order_id' => $orderId,
                'remark' => $remark,
                'operator_id' => auth()->id(),
            ]);

            Log::info('库存减少', [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'before' => $beforeQuantity,
                'after' => $afterQuantity,
            ]);

            return $log;
        });
    }

    /**
     * 调整库存
     */
    public function adjustStock(Product $product, int $newQuantity, string $remark = ''): InventoryLog
    {
        return DB::transaction(function () use ($product, $newQuantity, $remark) {
            if ($newQuantity < 0) {
                throw new \Exception('库存数量不能为负数');
            }

            $beforeQuantity = $product->stock_quantity;
            $quantity = $newQuantity - $beforeQuantity;

            $product->update(['stock_quantity' => $newQuantity]);

            // 更新商品状态
            if ($newQuantity === 0) {
                $product->update(['status' => 'sold_out']);
            } elseif ($product->status === 'sold_out' && $newQuantity > 0) {
                $product->update(['status' => 'active']);
            }

            $log = InventoryLog::create([
                'product_id' => $product->id,
                'type' => 'adjust',
                'quantity' => $quantity,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $newQuantity,
                'remark' => $remark,
                'operator_id' => auth()->id(),
            ]);

            Log::info('库存调整', [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'before' => $beforeQuantity,
                'after' => $newQuantity,
            ]);

            return $log;
        });
    }

}
