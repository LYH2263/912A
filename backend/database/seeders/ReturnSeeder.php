<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReturnSeeder extends Seeder
{
    public function run(): void
    {
        $targetReturns = 5;
        if (ReturnRequest::query()->count() >= $targetReturns) {
            return;
        }

        $admin = User::query()->where('email', 'admin@example.com')->first();

        $orders = Order::query()
            ->with('orderItems')
            ->whereIn('status', ['completed', 'shipped', 'paid'])
            ->orderBy('id')
            ->limit(5)
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        $statuses = ['pending', 'approved', 'rejected', 'completed', 'pending'];
        $types = ['return', 'return', 'exchange', 'return', 'exchange'];
        $reasons = [
            '商品与描述不符，申请退货退款',
            '收到时有轻微划痕，希望退货',
            '尺码不合适，申请换货',
            '七天无理由退货',
            '颜色与图片差异较大，申请换货',
        ];

        foreach ($orders as $index => $order) {
            $orderItem = $order->orderItems->first();
            if (!$orderItem) {
                continue;
            }

            $returnNo = sprintf('RET-DEMO-%04d', $index + 1);
            $status = $statuses[$index] ?? 'pending';
            $refundAmount = $status === 'rejected' ? 0 : (float) $orderItem->subtotal;

            ReturnRequest::updateOrCreate(
                ['return_no' => $returnNo],
                [
                    'order_id' => $order->id,
                    'order_item_id' => $orderItem->id,
                    'type' => $types[$index] ?? 'return',
                    'reason' => $reasons[$index] ?? '演示退换货申请',
                    'refund_amount' => $refundAmount,
                    'quantity' => min(1, (int) $orderItem->quantity),
                    'status' => $status,
                    'reject_reason' => $status === 'rejected' ? '已超过退换货期限' : null,
                    'approved_at' => in_array($status, ['approved', 'completed']) ? now()->subDays(3) : null,
                    'rejected_at' => $status === 'rejected' ? now()->subDays(2) : null,
                    'completed_at' => $status === 'completed' ? now()->subDay() : null,
                    'operator_id' => in_array($status, ['approved', 'rejected', 'completed']) ? $admin?->id : null,
                ]
            );
        }
    }
}
