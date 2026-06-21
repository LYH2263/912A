<?php

namespace Database\Seeders;

use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Database\Seeder;

class InventoryLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 只在日志较少时补齐，避免每次启动无限增长
        $targetLogs = 80;
        $existing = InventoryLog::query()->count();
        if ($existing >= $targetLogs) {
            return;
        }

        $products = Product::query()->orderBy('id')->get();
        if ($products->isEmpty()) {
            return;
        }

        $toCreate = $targetLogs - $existing;

        foreach (range(1, $toCreate) as $i) {
            $product = $products[$i % $products->count()];
            $before = (int) $product->stock_quantity;

            // 模拟盘点调整
            $delta = ($i % 2 === 0) ? 5 : -3;
            $after = max(0, $before + $delta);
            $actualDelta = $after - $before;

            if ($actualDelta === 0) {
                continue;
            }

            InventoryLog::create([
                'product_id' => $product->id,
                'type' => 'adjust',
                'quantity' => $actualDelta,
                'before_quantity' => $before,
                'after_quantity' => $after,
                'remark' => '初始化数据：库存盘点调整',
                'operator_id' => null,
            ]);

            $product->update(['stock_quantity' => $after]);
        }
    }
}

