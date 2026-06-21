<?php

namespace App\Services;

use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductSku;
use App\Repositories\ProductBatchRepository;
use App\Services\InventoryAlertService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class InventoryService
{
    public function __construct(
        private ProductBatchRepository $batchRepository,
        private InventoryAlertService $alertService
    ) {
    }

    /**
     * 触发库存预警更新（库存变动后调用）
     */
    private function triggerAlert(Product $product): void
    {
        try {
            $this->alertService->createOrUpdateAlert($product);
        } catch (\Exception $e) {
            Log::warning('库存预警更新失败', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function increaseStock(Product $product, int $quantity, ?int $orderId = null, string $remark = ''): InventoryLog
    {
        return DB::transaction(function () use ($product, $quantity, $orderId, $remark) {
            $beforeQuantity = $product->stock_quantity;
            $afterQuantity = $beforeQuantity + $quantity;

            $product->update(['stock_quantity' => $afterQuantity]);

            if ($product->status === 'sold_out' && $afterQuantity > 0) {
                $product->update(['status' => 'active']);
            }

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

    public function decreaseStock(Product $product, int $quantity, ?int $orderId = null, string $remark = ''): InventoryLog
    {
        return DB::transaction(function () use ($product, $quantity, $orderId, $remark) {
            if ($product->stock_quantity < $quantity) {
                throw new \Exception("库存不足，当前库存：{$product->stock_quantity}");
            }

            $beforeQuantity = $product->stock_quantity;
            $afterQuantity = $beforeQuantity - $quantity;

            $product->update(['stock_quantity' => $afterQuantity]);

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

    public function increaseSkuStock(ProductSku $sku, int $quantity, ?int $orderId = null, string $remark = ''): void
    {
        DB::transaction(function () use ($sku, $quantity, $orderId, $remark) {
            $beforeQuantity = $sku->stock_quantity;
            $afterQuantity = $beforeQuantity + $quantity;

            $sku->update(['stock_quantity' => $afterQuantity]);

            $product = $sku->product;
            $productTotalStock = $product->skus()->sum('stock_quantity');
            $product->update(['stock_quantity' => $productTotalStock]);

            if ($product->status === 'sold_out' && $productTotalStock > 0) {
                $product->update(['status' => 'active']);
            }

            InventoryLog::create([
                'product_id' => $product->id,
                'sku_id' => $sku->id,
                'type' => $orderId ? 'return' : 'in',
                'quantity' => $quantity,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $afterQuantity,
                'related_order_id' => $orderId,
                'remark' => $remark . ' (SKU: ' . $sku->sku . ')',
                'operator_id' => auth()->id(),
            ]);

            Log::info('SKU库存增加', [
                'sku_id' => $sku->id,
                'sku' => $sku->sku,
                'quantity' => $quantity,
                'before' => $beforeQuantity,
                'after' => $afterQuantity,
            ]);
        });
    }

    public function decreaseSkuStock(ProductSku $sku, int $quantity, ?int $orderId = null, string $remark = ''): void
    {
        DB::transaction(function () use ($sku, $quantity, $orderId, $remark) {
            if ($sku->stock_quantity < $quantity) {
                throw new \Exception("SKU {$sku->sku} 库存不足，当前库存：{$sku->stock_quantity}");
            }

            $beforeQuantity = $sku->stock_quantity;
            $afterQuantity = $beforeQuantity - $quantity;

            $sku->update(['stock_quantity' => $afterQuantity]);

            $product = $sku->product;
            $productTotalStock = $product->skus()->sum('stock_quantity');
            $product->update(['stock_quantity' => $productTotalStock]);

            if ($productTotalStock === 0) {
                $product->update(['status' => 'sold_out']);
            }

            InventoryLog::create([
                'product_id' => $product->id,
                'sku_id' => $sku->id,
                'type' => $orderId ? 'sale' : 'out',
                'quantity' => -$quantity,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $afterQuantity,
                'related_order_id' => $orderId,
                'remark' => $remark . ' (SKU: ' . $sku->sku . ')',
                'operator_id' => auth()->id(),
            ]);

            Log::info('SKU库存减少', [
                'sku_id' => $sku->id,
                'sku' => $sku->sku,
                'quantity' => $quantity,
                'before' => $beforeQuantity,
                'after' => $afterQuantity,
            ]);
        });
    }

    public function adjustStock(Product $product, int $newQuantity, string $remark = ''): InventoryLog
    {
        return DB::transaction(function () use ($product, $newQuantity, $remark) {
            if ($newQuantity < 0) {
                throw new \Exception('库存数量不能为负数');
            }

            $skuCount = $product->skus()->count();
            if ($skuCount > 0) {
                $currentTotal = $product->skus()->sum('stock_quantity');
                if ($newQuantity != $currentTotal) {
                    throw new \Exception("该商品存在 {$skuCount} 个SKU配置，请通过SKU维度调整库存。当前SKU库存合计：{$currentTotal}");
                }
            }

            $beforeQuantity = $product->stock_quantity;
            $quantity = $newQuantity - $beforeQuantity;

            if ($quantity == 0) {
                $log = InventoryLog::create([
                    'product_id' => $product->id,
                    'type' => 'adjust',
                    'quantity' => 0,
                    'before_quantity' => $beforeQuantity,
                    'after_quantity' => $newQuantity,
                    'remark' => $remark . ' [无变化]',
                    'operator_id' => auth()->id(),
                ]);
                return $log;
            }

            $product->update(['stock_quantity' => $newQuantity]);

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

    public function adjustSkuStock(ProductSku $sku, int $newQuantity, string $remark = ''): InventoryLog
    {
        return DB::transaction(function () use ($sku, $newQuantity, $remark) {
            if ($newQuantity < 0) {
                throw new \Exception('SKU库存数量不能为负数');
            }

            $beforeQuantity = $sku->stock_quantity;
            $quantity = $newQuantity - $beforeQuantity;

            $sku->update(['stock_quantity' => $newQuantity]);

            $product = $sku->product;
            $productTotalStock = $product->skus()->sum('stock_quantity');
            $productBefore = $product->stock_quantity;
            $product->update(['stock_quantity' => $productTotalStock]);

            if ($productTotalStock === 0) {
                $product->update(['status' => 'sold_out']);
            } elseif ($product->status === 'sold_out' && $productTotalStock > 0) {
                $product->update(['status' => 'active']);
            }

            $log = InventoryLog::create([
                'product_id' => $product->id,
                'sku_id' => $sku->id,
                'type' => 'adjust',
                'quantity' => $quantity,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $newQuantity,
                'remark' => $remark . ' (SKU: ' . $sku->sku . ')',
                'operator_id' => auth()->id(),
            ]);

            Log::info('SKU库存调整', [
                'sku_id' => $sku->id,
                'sku' => $sku->sku,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'sku_before' => $beforeQuantity,
                'sku_after' => $newQuantity,
                'product_before' => $productBefore,
                'product_after' => $productTotalStock,
            ]);

            return $log;
        });
    }

    public function createBatch(Product $product, array $batchData, string $remark = ''): ProductBatch
    {
        return DB::transaction(function () use ($product, $batchData, $remark) {
            if (empty($batchData['expiry_date']) && !empty($batchData['production_date']) && !empty($batchData['shelf_life_days'])) {
                $batchData['expiry_date'] = Carbon::parse($batchData['production_date'])->addDays($batchData['shelf_life_days']);
            }

            $quantity = (int) ($batchData['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw new \Exception('入库数量必须大于0');
            }

            $batchData['initial_quantity'] = $quantity;
            $batchData['product_id'] = $product->id;

            $batch = ProductBatch::create($batchData);

            $beforeQuantity = $product->stock_quantity;

            $this->recalculateSellableStock($product);

            $product->refresh();
            $afterQuantity = $product->stock_quantity;

            InventoryLog::create([
                'product_id' => $product->id,
                'sku_id' => $batchData['sku_id'] ?? null,
                'product_batch_id' => $batch->id,
                'type' => 'in',
                'quantity' => $batch->is_sellable ? $quantity : 0,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $afterQuantity,
                'remark' => $remark . ' [批次入库: ' . $batch->batch_no . ']' . ($batch->is_sellable ? '' : '（不可售批次，不计入可售库存）'),
                'operator_id' => auth()->id(),
            ]);

            Log::info('批次入库成功', [
                'batch_id' => $batch->id,
                'batch_no' => $batch->batch_no,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'is_sellable' => $batch->is_sellable,
            ]);

            return $batch->fresh();
        });
    }

    public function decreaseStockByFifo(Product $product, int $quantity, ?int $orderId = null, ?int $skuId = null, string $remark = ''): array
    {
        return DB::transaction(function () use ($product, $quantity, $orderId, $skuId, $remark) {
            $batches = $this->batchRepository->getBatchesForFifo($product->id, $skuId);

            $totalAvailable = $batches->sum('quantity');
            if ($totalAvailable < $quantity) {
                throw new \Exception("可售批次库存不足，当前可售：{$totalAvailable}，需要：{$quantity}");
            }

            $remaining = $quantity;
            $deductedBatches = [];
            $totalBefore = $product->stock_quantity;

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $deductQty = min($batch->quantity, $remaining);
                $batchBefore = $batch->quantity;
                $batchAfter = $batchBefore - $deductQty;

                $batch->update(['quantity' => $batchAfter]);

                $deductedBatches[] = [
                    'batch_id' => $batch->id,
                    'batch_no' => $batch->batch_no,
                    'quantity' => $deductQty,
                    'before' => $batchBefore,
                    'after' => $batchAfter,
                ];

                InventoryLog::create([
                    'product_id' => $product->id,
                    'sku_id' => $skuId,
                    'product_batch_id' => $batch->id,
                    'type' => $orderId ? 'sale' : 'out',
                    'quantity' => -$deductQty,
                    'before_quantity' => $batchBefore,
                    'after_quantity' => $batchAfter,
                    'related_order_id' => $orderId,
                    'remark' => $remark . ' [FIFO批次扣减: ' . $batch->batch_no . ']',
                    'operator_id' => auth()->id(),
                ]);

                $remaining -= $deductQty;
            }

            $totalBefore = $product->stock_quantity;

            $this->recalculateSellableStock($product);

            $product->refresh();
            $totalAfter = $product->stock_quantity;

            Log::info('FIFO批次扣减成功', [
                'product_id' => $product->id,
                'sku_id' => $skuId,
                'quantity' => $quantity,
                'batches_used' => count($deductedBatches),
                'order_id' => $orderId,
            ]);

            return [
                'total_deducted' => $quantity,
                'before_quantity' => $totalBefore,
                'after_quantity' => $totalAfter,
                'batches' => $deductedBatches,
            ];
        });
    }

    public function restoreStockToBatches(Product $product, int $quantity, ?int $orderId = null, ?int $skuId = null, string $remark = ''): array
    {
        return DB::transaction(function () use ($product, $quantity, $orderId, $skuId, $remark) {
            $batches = ProductBatch::where('product_id', $product->id)
                ->when($skuId, fn($q) => $q->where('sku_id', $skuId))
                ->when(is_null($skuId), fn($q) => $q->whereNull('sku_id'))
                ->where('is_sellable', true)
                ->orderBy('expiry_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $totalBefore = $product->stock_quantity;
            $restoredBatches = [];

            if ($batches->isNotEmpty()) {
                $remaining = $quantity;

                foreach ($batches as $batch) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $maxRestore = $batch->initial_quantity - $batch->quantity;
                    $restoreQty = min($maxRestore, $remaining);
                    if ($restoreQty <= 0) {
                        continue;
                    }

                    $batchBefore = $batch->quantity;
                    $batchAfter = $batchBefore + $restoreQty;

                    $batch->update([
                        'quantity' => $batchAfter,
                    ]);
                    $batch->refreshStatus();
                    $batch->save();

                    $restoredBatches[] = [
                        'batch_id' => $batch->id,
                        'batch_no' => $batch->batch_no,
                        'quantity' => $restoreQty,
                        'before' => $batchBefore,
                        'after' => $batchAfter,
                    ];

                    InventoryLog::create([
                        'product_id' => $product->id,
                        'sku_id' => $skuId,
                        'product_batch_id' => $batch->id,
                        'type' => $orderId ? 'return' : 'in',
                        'quantity' => $restoreQty,
                        'before_quantity' => $batchBefore,
                        'after_quantity' => $batchAfter,
                        'related_order_id' => $orderId,
                        'remark' => $remark . ' [批次归还: ' . $batch->batch_no . ']',
                        'operator_id' => auth()->id(),
                    ]);

                    $remaining -= $restoreQty;
                }

                if ($remaining > 0) {
                    Log::warning('批次归还库存有剩余，无匹配批次可归还', [
                        'product_id' => $product->id,
                        'sku_id' => $skuId,
                        'quantity' => $quantity,
                        'remaining' => $remaining,
                    ]);
                }
            } else {
                $this->increaseStock($product, $quantity, $orderId, $remark . ' [无批次记录，直接增加总库存]');
            }

            $this->recalculateSellableStock($product);

            $product->refresh();
            $totalAfter = $product->stock_quantity;

            Log::info('批次归还库存成功', [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'batches_used' => count($restoredBatches),
            ]);

            return [
                'total_restored' => $quantity,
                'before_quantity' => $totalBefore,
                'after_quantity' => $totalAfter,
                'batches' => $restoredBatches,
            ];
        });
    }

    public function adjustBatchQuantity(ProductBatch $batch, int $newQuantity, string $remark = ''): ProductBatch
    {
        return DB::transaction(function () use ($batch, $newQuantity, $remark) {
            if ($newQuantity < 0) {
                throw new \Exception('批次库存数量不能为负数');
            }

            $beforeQty = $batch->quantity;
            $diff = $newQuantity - $beforeQty;

            $batch->update(['quantity' => $newQuantity]);

            $product = $batch->product;
            $productBefore = $product->stock_quantity;

            $this->recalculateSellableStock($product);

            $product->refresh();
            $productAfter = $product->stock_quantity;

            InventoryLog::create([
                'product_id' => $product->id,
                'sku_id' => $batch->sku_id,
                'product_batch_id' => $batch->id,
                'type' => 'adjust',
                'quantity' => $batch->is_sellable ? $diff : 0,
                'before_quantity' => $beforeQty,
                'after_quantity' => $newQuantity,
                'remark' => $remark . ' [批次调整: ' . $batch->batch_no . ']' . ($batch->is_sellable ? '' : '（不可售批次，不计入可售库存变动）'),
                'operator_id' => auth()->id(),
            ]);

            Log::info('批次库存调整', [
                'batch_id' => $batch->id,
                'before' => $beforeQty,
                'after' => $newQuantity,
                'is_sellable' => $batch->is_sellable,
                'product_before' => $productBefore,
                'product_after' => $productAfter,
            ]);

            return $batch->fresh();
        });
    }

    public function markBatchAsUnsellable(ProductBatch $batch, string $remark = ''): ProductBatch
    {
        return DB::transaction(function () use ($batch, $remark) {
            $beforeQty = $batch->quantity;
            $product = $batch->product;
            $productBefore = $product->stock_quantity;

            $batch->update([
                'is_sellable' => false,
            ]);

            $this->recalculateSellableStock($product);

            $product->refresh();
            $productAfter = $product->stock_quantity;

            InventoryLog::create([
                'product_id' => $product->id,
                'sku_id' => $batch->sku_id,
                'product_batch_id' => $batch->id,
                'type' => 'adjust',
                'quantity' => -$beforeQty,
                'before_quantity' => $productBefore,
                'after_quantity' => $productAfter,
                'remark' => $remark . ' [批次标记不可售: ' . $batch->batch_no . ']',
                'operator_id' => auth()->id(),
            ]);

            Log::info('批次标记不可售', [
                'batch_id' => $batch->id,
                'batch_no' => $batch->batch_no,
                'quantity' => $beforeQty,
            ]);

            return $batch->fresh();
        });
    }

    public function getProductAvailableBatchStock(int $productId, ?int $skuId = null): int
    {
        return (int) ProductBatch::where('product_id', $productId)
            ->when($skuId, fn($q) => $q->where('sku_id', $skuId))
            ->when(is_null($skuId), fn($q) => $q->whereNull('sku_id'))
            ->where('is_sellable', true)
            ->sum('quantity');
    }

    public function recalculateSellableStock(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $productSellableQty = (int) ProductBatch::where('product_id', $product->id)
                ->where('is_sellable', true)
                ->sum('quantity');

            $productHasBatches = ProductBatch::where('product_id', $product->id)->exists();

            if ($productHasBatches) {
                $product->update(['stock_quantity' => $productSellableQty]);

                if ($productSellableQty === 0) {
                    $product->update(['status' => 'sold_out']);
                } elseif ($product->status === 'sold_out' && $productSellableQty > 0) {
                    $product->update(['status' => 'active']);
                }

                $skus = $product->skus;
                if ($skus->isNotEmpty()) {
                    foreach ($skus as $sku) {
                        $skuSellableQty = (int) ProductBatch::where('sku_id', $sku->id)
                            ->where('is_sellable', true)
                            ->sum('quantity');
                        $sku->update(['stock_quantity' => $skuSellableQty]);
                    }

                    $skuTotal = $skus->sum('stock_quantity');
                    $product->update(['stock_quantity' => $skuTotal]);

                    if ($skuTotal === 0) {
                        $product->update(['status' => 'sold_out']);
                    } elseif ($product->status === 'sold_out' && $skuTotal > 0) {
                        $product->update(['status' => 'active']);
                    }
                }
            }

            Log::info('重新计算可售库存', [
                'product_id' => $product->id,
                'stock_quantity' => $product->stock_quantity,
            ]);
        });
    }
}
