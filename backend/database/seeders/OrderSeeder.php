<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 目标：保证至少有 12 条演示订单（可重复启动，不会无限增长）
        $targetOrders = 12;
        $existing = Order::query()->count();
        if ($existing >= $targetOrders) {
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

        $toCreate = $targetOrders - $existing;

        foreach (range(1, $toCreate) as $i) {
            $orderNo = sprintf('DEMO-%04d', $existing + $i);

            $statusPool = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];
            $status = $statusPool[($existing + $i) % count($statusPool)];

            $order = Order::updateOrCreate(
                ['order_no' => $orderNo],
                [
                    'total_amount' => 0,
                    'discount_amount' => 0,
                    'final_amount' => 0,
                    'status' => $status,
                    'shipping_name' => ($i % 2 === 0) ? '张三' : '李四',
                    'shipping_phone' => ($i % 2 === 0) ? '13800138000' : '13900139000',
                    'shipping_address' => ($i % 2 === 0) ? '北京市朝阳区xxx街道xxx号' : '上海市浦东新区xxx路xxx号',
                    'paid_at' => in_array($status, ['paid', 'shipped', 'completed']) ? now()->subDays(1) : null,
                    'shipped_at' => in_array($status, ['shipped', 'completed']) ? now()->subDays(1) : null,
                    'completed_at' => $status === 'completed' ? now()->subDays(1) : null,
                    'cancelled_at' => $status === 'cancelled' ? now()->subDays(1) : null,
                ]
            );

            // 如果该订单已有关联订单项，跳过（保证幂等）
            if ($order->orderItems()->exists()) {
                continue;
            }

            $skuList = array_values($products->keys()->all());
            $sku1 = $skuList[($existing + $i) % count($skuList)];
            $sku2 = $skuList[($existing + $i + 1) % count($skuList)];

            $p1 = $products[$sku1];
            $p2 = $products[$sku2];

            $q1 = (($existing + $i) % 3) + 1;
            $q2 = (($existing + $i + 1) % 2) + 1;

            $sub1 = (float) $p1->price * $q1;
            $sub2 = (float) $p2->price * $q2;
            $total = $sub1 + $sub2;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $p1->id,
                'product_name' => $p1->name,
                'product_sku' => $p1->sku,
                'product_price' => $p1->price,
                'quantity' => $q1,
                'subtotal' => $sub1,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $p2->id,
                'product_name' => $p2->name,
                'product_sku' => $p2->sku,
                'product_price' => $p2->price,
                'quantity' => $q2,
                'subtotal' => $sub2,
            ]);

            $order->update([
                'total_amount' => $total,
                'final_amount' => $total,
            ]);
        }
    }
}
