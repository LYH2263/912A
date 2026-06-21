<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
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
            $orderItem = OrderItem::findOrFail($data['order_item_id']);
            $order = Order::findOrFail($data['order_id']);

            if (!in_array($order->status, ['paid', 'shipped'])) {
                throw new \Exception('仅已支付或已发货的订单可申请退换货');
            }

            if ($data['quantity'] > $orderItem->quantity) {
                throw new \Exception('退货数量不能超过订单商品数量');
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

    private function restoreInventoryForReturn(ReturnRequest $return): void
    {
        $orderItem = $return->orderItem;
        $quantity = $return->quantity;

        if (!empty($orderItem->product_sku_id)) {
            $sku = ProductSku::find($orderItem->product_sku_id);
            if ($sku) {
                $this->inventoryService->increaseSkuStock($sku, $quantity, $return->order_id, '退货入库');
            }
        } else {
            $product = Product::findOrFail($orderItem->product_id);
            $this->inventoryService->increaseStock($product, $quantity, $return->order_id, '退货入库');
        }
    }
}
