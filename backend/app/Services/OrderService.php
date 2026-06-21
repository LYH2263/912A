<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Repositories\OrderRepository;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        public OrderRepository $repository,
        private InventoryService $inventoryService
    ) {
    }

    /**
     * 创建订单
     */
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];
            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                if (!$product->hasEnoughStock($item['quantity'])) {
                    throw new \Exception("商品 {$product->name} 库存不足，当前库存：{$product->stock_quantity}");
                }
                if ($product->status !== 'active') {
                    throw new \Exception("商品 {$product->name} 已下架，无法购买");
                }
            }

            $orderNo = Order::generateOrderNo();

            $totalAmount = 0;
            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $subtotal = $product->price * $item['quantity'];
                $totalAmount += $subtotal;
            }

            $discountAmount = 0;
            $couponId = null;
            $user = auth()->user();

            if (!empty($data['coupon_id'])) {
                $coupon = Coupon::findOrFail($data['coupon_id']);
                $couponService = app(CouponService::class);
                $result = $couponService->validateAndCalculate($coupon, $totalAmount, $user);
                $discountAmount = $result['discount_amount'];
                $couponId = $coupon->id;

                $couponService->markAsUsed($coupon, $user);
            } else {
                $discountAmount = $data['discount_amount'] ?? 0;
            }

            $finalAmount = $totalAmount - $discountAmount;

            $order = $this->repository->create([
                'order_no' => $orderNo,
                'user_id' => $data['user_id'] ?? null,
                'coupon_id' => $couponId,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'status' => 'pending',
                'shipping_address' => $data['shipping_address'] ?? null,
                'shipping_name' => $data['shipping_name'] ?? null,
                'shipping_phone' => $data['shipping_phone'] ?? null,
                'remark' => $data['remark'] ?? null,
            ]);

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $subtotal = $product->price * $item['quantity'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'product_price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                ]);

                $this->inventoryService->decreaseStock($product, $item['quantity'], $order->id, '订单创建');
            }

            Log::info('订单创建成功', ['order_id' => $order->id, 'order_no' => $order->order_no]);

            return $order->load('orderItems');
        });
    }

    /**
     * 更新订单状态
     */
    public function updateStatus(Order $order, string $status): Order
    {
        $oldStatus = $order->status;

        // 状态流转验证
        $allowedTransitions = [
            'pending' => ['paid', 'cancelled'],
            'paid' => ['shipped', 'cancelled'],
            'shipped' => ['completed', 'cancelled'],
        ];

        if (!in_array($status, $allowedTransitions[$oldStatus] ?? [])) {
            throw new \Exception("订单状态不能从 {$oldStatus} 直接变更为 {$status}");
        }

        $updateData = ['status' => $status];

        // 记录状态变更时间
        switch ($status) {
            case 'paid':
                $updateData['paid_at'] = now();
                break;
            case 'shipped':
                $updateData['shipped_at'] = now();
                break;
            case 'completed':
                $updateData['completed_at'] = now();
                break;
            case 'cancelled':
                $updateData['cancelled_at'] = now();
                // 恢复库存
                $this->restoreInventory($order);
                break;
        }

        $order = $this->repository->update($order, $updateData);

        Log::info('订单状态更新', [
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $status,
        ]);

        return $order;
    }

    /**
     * 恢复库存（订单取消时）
     */
    private function restoreInventory(Order $order): void
    {
        foreach ($order->orderItems as $item) {
            $product = Product::findOrFail($item->product_id);
            $this->inventoryService->increaseStock($product, $item->quantity, $order->id, '订单取消');
        }
    }
}
