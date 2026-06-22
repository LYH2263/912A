<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductSku;
use App\Models\ReturnRequest;
use App\Repositories\ReturnRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReturnService
{
    public function __construct(
        public ReturnRepository $repository,
        private InventoryService $inventoryService
    ) {
    }

    public function create(array $data): ReturnRequest
    {
        return DB::transaction(function () use ($data) {
            $order = Order::findOrFail($data['order_id']);
            $orderItem = OrderItem::findOrFail($data['order_item_id']);

            if ($orderItem->order_id !== $order->id) {
                throw new \Exception('所选商品项不属于该订单');
            }

            if (!in_array($order->status, ['paid', 'shipped'])) {
                throw new \Exception('仅已支付或已发货的订单可申请退换货');
            }

            $appliedQuantity = $this->getAppliedQuantity($orderItem->id);
            $remainingQuantity = $orderItem->quantity - $appliedQuantity;

            if ($remainingQuantity <= 0) {
                throw new \Exception('该商品已全部申请退换货，不可重复申请');
            }

            if ($data['quantity'] > $remainingQuantity) {
                throw new \Exception("退货数量超过剩余可退数量，剩余可退：{$remainingQuantity}");
            }

            if (empty($data['refund_amount']) || $data['refund_amount'] < 0) {
                throw new \Exception('退款金额必须大于等于0');
            }

            $maxRefund = $orderItem->product_price * $data['quantity'];
            if ($data['refund_amount'] > $maxRefund) {
                throw new \Exception("退款金额不能超过 {$maxRefund}");
            }

            $returnNo = ReturnRequest::generateReturnNo();

            $return = $this->repository->create([
                'return_no' => $returnNo,
                'order_id' => $order->id,
                'order_item_id' => $orderItem->id,
                'type' => $data['type'],
                'reason' => $data['reason'],
                'refund_amount' => $data['refund_amount'],
                'quantity' => $data['quantity'],
                'status' => 'pending',
                'operator_id' => auth()->id(),
            ]);

            Log::info('退换货申请创建成功', [
                'return_id' => $return->id,
                'return_no' => $return->return_no,
                'order_id' => $order->id,
            ]);

            return $return->load(['order', 'orderItem.product']);
        });
    }

    private function getAppliedQuantity(int $orderItemId): int
    {
        return (int) ReturnRequest::where('order_item_id', $orderItemId)
            ->whereIn('status', ['pending', 'approved', 'completed'])
            ->sum('quantity');
    }

    public function approve(ReturnRequest $return): ReturnRequest
    {
        return DB::transaction(function () use ($return) {
            $this->validateStatusTransition($return->status, 'approved');

            $return = $this->repository->update($return, [
                'status' => 'approved',
                'approved_at' => now(),
                'operator_id' => auth()->id(),
            ]);

            Log::info('退换货申请已通过', [
                'return_id' => $return->id,
                'return_no' => $return->return_no,
            ]);

            return $return;
        });
    }

    public function reject(ReturnRequest $return, string $rejectReason): ReturnRequest
    {
        return DB::transaction(function () use ($return, $rejectReason) {
            $this->validateStatusTransition($return->status, 'rejected');

            $return = $this->repository->update($return, [
                'status' => 'rejected',
                'rejected_at' => now(),
                'reject_reason' => $rejectReason,
                'operator_id' => auth()->id(),
            ]);

            Log::info('退换货申请已拒绝', [
                'return_id' => $return->id,
                'return_no' => $return->return_no,
                'reject_reason' => $rejectReason,
            ]);

            return $return;
        });
    }

    public function complete(ReturnRequest $return): ReturnRequest
    {
        return DB::transaction(function () use ($return) {
            $this->validateStatusTransition($return->status, 'completed');

            if ($return->type === 'return') {
                $this->restoreInventoryForReturn($return);
            } elseif ($return->type === 'exchange') {
                $this->restoreInventoryForReturn($return, '换货退回');
                $this->deductInventoryForExchange($return);
            }

            $return = $this->repository->update($return, [
                'status' => 'completed',
                'completed_at' => now(),
                'operator_id' => auth()->id(),
            ]);

            Log::info('退换货已完成', [
                'return_id' => $return->id,
                'return_no' => $return->return_no,
            ]);

            return $return;
        });
    }

    private function validateStatusTransition(string $oldStatus, string $newStatus): void
    {
        $allowedTransitions = [
            'pending' => ['approved', 'rejected'],
            'approved' => ['completed'],
            'rejected' => [],
            'completed' => [],
        ];

        if (!in_array($newStatus, $allowedTransitions[$oldStatus] ?? [])) {
            $statusMap = [
                'pending' => '待审核',
                'approved' => '已通过',
                'rejected' => '已拒绝',
                'completed' => '已完成',
            ];
            throw new \Exception("退换货状态不能从 {$statusMap[$oldStatus]} 直接变更为 {$statusMap[$newStatus]}");
        }
    }

    private function restoreInventoryForReturn(ReturnRequest $return, string $remark = '退货入库'): void
    {
        $orderItem = $return->orderItem;
        $quantity = $return->quantity;
        $orderId = $return->order_id;

        if (!empty($orderItem->product_sku_id)) {
            $sku = ProductSku::find($orderItem->product_sku_id);
            if ($sku) {
                $product = $sku->product;

                $hasBatches = ProductBatch::where('product_id', $product->id)
                    ->where('sku_id', $sku->id)
                    ->exists();

                if ($hasBatches) {
                    $this->inventoryService->restoreStockToBatches($product, $quantity, $orderId, $sku->id, $remark);
                } else {
                    $this->inventoryService->increaseSkuStock($sku, $quantity, $orderId, $remark);
                }
            }
        } else {
            $product = Product::findOrFail($orderItem->product_id);

            $hasBatches = ProductBatch::where('product_id', $product->id)
                ->whereNull('sku_id')
                ->exists();

            if ($hasBatches) {
                $this->inventoryService->restoreStockToBatches($product, $quantity, $orderId, null, $remark);
            } else {
                $this->inventoryService->increaseStock($product, $quantity, $orderId, $remark);
            }
        }
    }

    private function deductInventoryForExchange(ReturnRequest $return): void
    {
        $orderItem = $return->orderItem;
        $quantity = $return->quantity;
        $orderId = $return->order_id;

        if (!empty($orderItem->product_sku_id)) {
            $sku = ProductSku::findOrFail($orderItem->product_sku_id);
            $product = $sku->product;

            $isBatchManaged = ProductBatch::where('product_id', $product->id)
                ->where('sku_id', $sku->id)
                ->exists();

            if ($isBatchManaged) {
                $sellableQty = $this->inventoryService->getProductAvailableBatchStock($product->id, $sku->id);
                if ($sellableQty < $quantity) {
                    throw new \Exception("换货出库失败：商品 {$product->name} ({$sku->sku}) 可售批次库存不足，当前可售：{$sellableQty}，需要：{$quantity}");
                }
                $this->inventoryService->decreaseStockByFifo($product, $quantity, $orderId, $sku->id, '换货出库');
            } else {
                $this->inventoryService->decreaseSkuStock($sku, $quantity, $orderId, '换货出库');
            }
        } else {
            $product = Product::findOrFail($orderItem->product_id);

            $isBatchManaged = ProductBatch::where('product_id', $product->id)
                ->whereNull('sku_id')
                ->exists();

            if ($isBatchManaged) {
                $sellableQty = $this->inventoryService->getProductAvailableBatchStock($product->id);
                if ($sellableQty < $quantity) {
                    throw new \Exception("换货出库失败：商品 {$product->name} 可售批次库存不足，当前可售：{$sellableQty}，需要：{$quantity}");
                }
                $this->inventoryService->decreaseStockByFifo($product, $quantity, $orderId, null, '换货出库');
            } else {
                $this->inventoryService->decreaseStock($product, $quantity, $orderId, '换货出库');
            }
        }
    }
}
