<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $targetOrders = 12;
        $existing = Order::query()->count();
        if ($existing >= $targetOrders) {
            $this->syncOrderRelations();
            return;
        }

        $products = Product::query()
            ->whereIn('sku', [
                'IPHONE15PRO001',
                'HUAWEIMATE60001',
                'XIAOMI14PRO001',
                'MACBOOKPRO14001',
                'THINKPADX1001',
                'TSHIRT001',
                'DRESS_W_001',
                'BOOK_LARAVEL_10',
            ])->get()->keyBy('sku');

        if ($products->isEmpty()) {
            return;
        }

        $customers = Customer::query()->get()->keyBy('phone');
        $coupons = Coupon::query()->whereIn('code', ['WELCOME50', 'SAVE10', 'VIP100'])->get()->keyBy('code');

        $toCreate = $targetOrders - $existing;

        foreach (range(1, $toCreate) as $i) {
            $orderIndex = $existing + $i;
            $orderNo = sprintf('DEMO-%04d', $orderIndex);

            $statusPool = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];
            $status = $statusPool[$orderIndex % count($statusPool)];

            $shippingPhone = ($orderIndex % 2 === 0) ? '13800138000' : '13900139000';
            $customer = $customers[$shippingPhone] ?? $customers->first();

            $coupon = null;
            $discountAmount = 0;
            if (in_array($status, ['paid', 'shipped', 'completed']) && $orderIndex % 3 === 0) {
                $coupon = $coupons['WELCOME50'] ?? null;
                $discountAmount = $coupon ? 50.00 : 0;
            } elseif (in_array($status, ['paid', 'shipped', 'completed']) && $orderIndex % 3 === 1) {
                $coupon = $coupons['SAVE10'] ?? null;
            }

            $order = Order::updateOrCreate(
                ['order_no' => $orderNo],
                [
                    'customer_id' => $customer?->id,
                    'coupon_id' => $coupon?->id,
                    'total_amount' => 0,
                    'discount_amount' => $discountAmount,
                    'final_amount' => 0,
                    'status' => $status,
                    'shipping_name' => $customer?->name ?? (($orderIndex % 2 === 0) ? '张三' : '李四'),
                    'shipping_phone' => $shippingPhone,
                    'shipping_address' => $customer?->address ?? (($orderIndex % 2 === 0) ? '北京市朝阳区xxx街道xxx号' : '上海市浦东新区xxx路xxx号'),
                    'paid_at' => in_array($status, ['paid', 'shipped', 'completed']) ? now()->subDays(1) : null,
                    'shipped_at' => in_array($status, ['shipped', 'completed']) ? now()->subDays(1) : null,
                    'completed_at' => $status === 'completed' ? now()->subDays(1) : null,
                    'cancelled_at' => $status === 'cancelled' ? now()->subDays(1) : null,
                ]
            );

            if ($order->orderItems()->exists()) {
                continue;
            }

            $skuList = array_values($products->keys()->all());
            $sku1 = $skuList[$orderIndex % count($skuList)];
            $sku2 = $skuList[($orderIndex + 1) % count($skuList)];

            $p1 = $products[$sku1];
            $p2 = $products[$sku2];

            $q1 = ($orderIndex % 3) + 1;
            $q2 = (($orderIndex + 1) % 2) + 1;

            $sub1 = (float) $p1->price * $q1;
            $sub2 = (float) $p2->price * $q2;
            $total = $sub1 + $sub2;

            if ($coupon && $coupon->code === 'SAVE10') {
                $discountAmount = round($total * 0.1, 2);
            }

            $productSku1 = ProductSku::query()->where('product_id', $p1->id)->first();
            $productSku2 = ProductSku::query()->where('product_id', $p2->id)->first();

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $p1->id,
                'product_sku_id' => $productSku1?->id,
                'product_name' => $p1->name,
                'product_sku' => $productSku1?->sku ?? $p1->sku,
                'product_price' => $productSku1?->price ?? $p1->price,
                'quantity' => $q1,
                'subtotal' => $productSku1 ? (float) $productSku1->price * $q1 : $sub1,
                'spec_snapshot' => $productSku1?->spec_data,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $p2->id,
                'product_sku_id' => $productSku2?->id,
                'product_name' => $p2->name,
                'product_sku' => $productSku2?->sku ?? $p2->sku,
                'product_price' => $productSku2?->price ?? $p2->price,
                'quantity' => $q2,
                'subtotal' => $productSku2 ? (float) $productSku2->price * $q2 : $sub2,
                'spec_snapshot' => $productSku2?->spec_data,
            ]);

            $finalAmount = max(0, $total - $discountAmount);

            $order->update([
                'total_amount' => $total,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
            ]);
        }

        $this->syncOrderRelations();
    }

    private function syncOrderRelations(): void
    {
        $customers = Customer::query()->get()->keyBy('phone');
        $coupon = Coupon::query()->where('code', 'WELCOME50')->first();

        Order::query()->where('order_no', 'like', 'DEMO-%')->each(function (Order $order) use ($customers, $coupon) {
            $updates = [];

            if (!$order->customer_id && $order->shipping_phone) {
                $updates['customer_id'] = $customers[$order->shipping_phone]?->id;
            }

            if (!$order->coupon_id && $coupon && in_array($order->status, ['paid', 'shipped', 'completed']) && ((int) substr($order->order_no, -4)) % 3 === 0) {
                $updates['coupon_id'] = $coupon->id;
                $updates['discount_amount'] = 50.00;
                $updates['final_amount'] = max(0, (float) $order->total_amount - 50.00);
            }

            if (!empty($updates)) {
                $order->update($updates);
            }

            $order->orderItems->each(function (OrderItem $item) {
                if ($item->product_sku_id) {
                    return;
                }

                $productSku = ProductSku::query()->where('product_id', $item->product_id)->first();
                if (!$productSku) {
                    return;
                }

                $item->update([
                    'product_sku_id' => $productSku->id,
                    'product_sku' => $productSku->sku,
                    'product_price' => $productSku->price,
                    'spec_snapshot' => $productSku->spec_data,
                ]);
            });
        });

        Customer::query()->each(function (Customer $customer) {
            $customer->updateStats();
        });
    }
}
