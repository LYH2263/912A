<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSku;
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

    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];
            $totalAmount = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->status !== 'active') {
                    throw new \Exception("商品 {$product->name} 已下架，无法购买");
                }

                if (!empty($item['product_sku_id'])) {
                    $sku = ProductSku::findOrFail($item['product_sku_id']);
                    if (!$sku->hasEnoughStock($item['quantity'])) {
                        throw new \Exception("商品 {$product->name} ({$sku->sku}) 库存不足，当前库存：{$sku->stock_quantity}");
                    }
                    $price = $sku->price;
                    $skuCode = $sku->sku;
                    $specSnapshot = $sku->spec_data;
                } else {
                    if (!$product->hasEnoughStock($item['quantity'])) {
                        throw new \Exception("商品 {$product->name} 库存不足，当前库存：{$product->stock_quantity}");
                    }
                    $price = $product->price;
                    $skuCode = $product->sku;
                    $specSnapshot = null;
                }

                $subtotal = $price * $item['quantity'];
                $totalAmount += $subtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_sku_id' => $item['product_sku_id'] ?? null,
                    'product_name' => $product->name,
                    'product_sku' => $skuCode,
                    'product_price' => $price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                    'spec_snapshot' => $specSnapshot,
                ];
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

            $orderNo = Order::generateOrderNo();

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

            foreach ($orderItems as $itemData) {
                $orderItem = OrderItem::create(array_merge($itemData, ['order_id' => $order->id]));

                if (!empty($itemData['product_sku_id'])) {
                    $sku = ProductSku::findOrFail($itemData['product_sku_id']);
                    $this->inventoryService->decreaseSkuStock($sku, $itemData['quantity'], $order->id, '订单创建');
                } else {
                    $product = Product::findOrFail($itemData['product_id']);
                    $this->inventoryService->decreaseStock($product, $itemData['quantity'], $order->id, '订单创建');
                }
            }

            Log::info('订单创建成功', ['order_id' => $order->id, 'order_no' => $order->order_no]);

            return $order->load('orderItems');
        });
    }

    public function updateStatus(Order $order, string $status): Order
    {
        $oldStatus = $order->status;

        $allowedTransitions = [
            'pending' => ['paid', 'cancelled'],
            'paid' => ['shipped', 'cancelled'],
            'shipped' => ['completed', 'cancelled'],
        ];

        if (!in_array($status, $allowedTransitions[$oldStatus] ?? [])) {
            throw new \Exception("订单状态不能从 {$oldStatus} 直接变更为 {$status}");
        }

        $updateData = ['status' => $status];

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

    private function restoreInventory(Order $order): void
    {
        foreach ($order->orderItems as $item) {
            if (!empty($item->product_sku_id)) {
                $sku = ProductSku::find($item->product_sku_id);
                if ($sku) {
                    $this->inventoryService->increaseSkuStock($sku, $item->quantity, $order->id, '订单取消');
                }
            } else {
                $product = Product::findOrFail($item->product_id);
                $this->inventoryService->increaseStock($product, $item->quantity, $order->id, '订单取消');
            }
        }
    }
}
