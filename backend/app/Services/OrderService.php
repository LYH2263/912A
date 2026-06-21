<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSku;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderRepository;
use App\Services\CustomerService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        public OrderRepository $repository,
        private InventoryService $inventoryService,
        private CustomerService $customerService,
        private CustomerRepository $customerRepository
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
                    $sku = ProductSku::find($item['product_sku_id']);
                    if (!$sku) {
                        throw new \Exception("SKU 不存在");
                    }
                    if ($sku->product_id !== $product->id) {
                        throw new \Exception("SKU {$sku->sku} 不属于商品 {$product->name}");
                    }
                    if ($sku->status !== 'active') {
                        throw new \Exception("SKU {$sku->sku} 已停用，无法购买");
                    }
                    if (!$sku->hasEnoughStock($item['quantity'])) {
                        throw new \Exception("商品 {$product->name} ({$sku->sku}) 库存不足，当前库存：{$sku->stock_quantity}");
                    }
                    $price = $sku->price;
                    $skuCode = $sku->sku;
                    $specSnapshot = $sku->spec_data;
                } else {
                    if ($product->has_specs) {
                        throw new \Exception("商品 {$product->name} 为多规格商品，请选择具体规格");
                    }
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

            $customerId = $data['customer_id'] ?? null;
            $shippingName = $data['shipping_name'] ?? null;
            $shippingPhone = $data['shipping_phone'] ?? null;
            $shippingAddress = $data['shipping_address'] ?? null;
            $customer = null;

            if (!empty($data['customer_id'])) {
                $customer = $this->customerRepository->find($data['customer_id']);
                if ($customer) {
                    $customerId = $customer->id;
                    if (empty($shippingName)) $shippingName = $customer->name;
                    if (empty($shippingPhone)) $shippingPhone = $customer->phone;
                    if (empty($shippingAddress)) $shippingAddress = $customer->address;
                }
            } elseif (!empty($shippingName) && !empty($shippingPhone)) {
                $customer = $this->customerService->findOrCreateByShippingInfo(
                    $shippingName,
                    $shippingPhone,
                    $shippingAddress
                );
                $customerId = $customer->id;
            }

            $discountAmount = 0;
            $couponId = null;

            if (!empty($data['coupon_id'])) {
                $coupon = Coupon::findOrFail($data['coupon_id']);
                $couponService = app(CouponService::class);
                $result = $couponService->validateAndCalculate($coupon, $totalAmount, $customer);
                $discountAmount = $result['discount_amount'];
                $couponId = $coupon->id;

                $couponService->markAsUsed($coupon, $customer);
            } else {
                $discountAmount = $data['discount_amount'] ?? 0;
            }

            $finalAmount = $totalAmount - $discountAmount;

            $orderNo = Order::generateOrderNo();

            $order = $this->repository->create([
                'customer_id' => $customerId,
                'order_no' => $orderNo,
                'user_id' => $data['user_id'] ?? null,
                'coupon_id' => $couponId,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'status' => 'pending',
                'shipping_address' => $shippingAddress,
                'shipping_name' => $shippingName,
                'shipping_phone' => $shippingPhone,
                'remark' => $data['remark'] ?? null,
            ]);

            if ($customerId) {
                $this->customerService->updateStats(Customer::find($customerId));
            }

            foreach ($orderItems as $itemData) {
                $orderItem = OrderItem::create(array_merge($itemData, ['order_id' => $order->id]));

                if (!empty($itemData['product_sku_id'])) {
                    $sku = ProductSku::findOrFail($itemData['product_sku_id']);
                    $product = $sku->product;

                    $isBatchManaged = \App\Models\ProductBatch::where('product_id', $product->id)
                        ->where('sku_id', $sku->id)
                        ->exists();

                    if ($isBatchManaged) {
                        $sellableQty = (int) \App\Models\ProductBatch::where('product_id', $product->id)
                            ->where('sku_id', $sku->id)
                            ->where('is_sellable', true)
                            ->sum('quantity');

                        if ($sellableQty < $itemData['quantity']) {
                            throw new \Exception("商品 {$product->name} ({$sku->sku}) 可售批次库存不足，当前可售：{$sellableQty}，需要：{$itemData['quantity']}");
                        }

                        $this->inventoryService->decreaseStockByFifo($product, $itemData['quantity'], $order->id, $sku->id, '订单创建');
                    } else {
                        $this->inventoryService->decreaseSkuStock($sku, $itemData['quantity'], $order->id, '订单创建');
                    }
                } else {
                    $product = Product::findOrFail($itemData['product_id']);

                    $isBatchManaged = \App\Models\ProductBatch::where('product_id', $product->id)
                        ->whereNull('sku_id')
                        ->exists();

                    if ($isBatchManaged) {
                        $sellableQty = (int) \App\Models\ProductBatch::where('product_id', $product->id)
                            ->whereNull('sku_id')
                            ->where('is_sellable', true)
                            ->sum('quantity');

                        if ($sellableQty < $itemData['quantity']) {
                            throw new \Exception("商品 {$product->name} 可售批次库存不足，当前可售：{$sellableQty}，需要：{$itemData['quantity']}");
                        }

                        $this->inventoryService->decreaseStockByFifo($product, $itemData['quantity'], $order->id, null, '订单创建');
                    } else {
                        $this->inventoryService->decreaseStock($product, $itemData['quantity'], $order->id, '订单创建');
                    }
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

        if ($order->customer_id) {
            $this->customerService->updateStats(Customer::find($order->customer_id));
        }

        return $order;
    }

    private function restoreInventory(Order $order): void
    {
        foreach ($order->orderItems as $item) {
            if (!empty($item->product_sku_id)) {
                $sku = ProductSku::find($item->product_sku_id);
                if ($sku) {
                    $product = $sku->product;

                    $isBatchManaged = \App\Models\ProductBatch::where('product_id', $product->id)
                        ->where('sku_id', $sku->id)
                        ->exists();

                    if ($isBatchManaged) {
                        $this->inventoryService->restoreStockToBatches($product, $item->quantity, $order->id, $sku->id, '订单取消');
                    } else {
                        $this->inventoryService->increaseSkuStock($sku, $item->quantity, $order->id, '订单取消');
                    }
                }
            } else {
                $product = Product::findOrFail($item->product_id);

                $isBatchManaged = \App\Models\ProductBatch::where('product_id', $product->id)
                    ->whereNull('sku_id')
                    ->exists();

                if ($isBatchManaged) {
                    $this->inventoryService->restoreStockToBatches($product, $item->quantity, $order->id, null, '订单取消');
                } else {
                    $this->inventoryService->increaseStock($product, $item->quantity, $order->id, '订单取消');
                }
            }
        }
    }
}
