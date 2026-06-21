<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProductBatchSeeder extends Seeder
{
    public function run(): void
    {
        $targetBatches = 12;
        if (ProductBatch::query()->count() >= $targetBatches) {
            return;
        }

        $kitchen = Product::query()->where('sku', 'KITCHEN_PAN_001')->first();
        $book = Product::query()->where('sku', 'BOOK_LARAVEL_10')->first();
        $storage = Product::query()->where('sku', 'STORAGE_BOX_001')->first();

        if (!$kitchen || !$book || !$storage) {
            return;
        }

        $today = Carbon::today();

        $batches = [
            [
                'product_id' => $kitchen->id,
                'batch_no' => 'KP202601-A',
                'production_date' => $today->copy()->subDays(30),
                'shelf_life_days' => 365,
                'quantity' => 40,
                'initial_quantity' => 50,
                'unit_cost' => 95.00,
                'remark' => '正常批次，库存充足',
            ],
            [
                'product_id' => $kitchen->id,
                'batch_no' => 'KP202602-B',
                'production_date' => $today->copy()->subDays(350),
                'shelf_life_days' => 365,
                'quantity' => 15,
                'initial_quantity' => 30,
                'unit_cost' => 92.00,
                'remark' => '临期批次，优先出库',
            ],
            [
                'product_id' => $kitchen->id,
                'batch_no' => 'KP202501-C',
                'production_date' => $today->copy()->subDays(400),
                'shelf_life_days' => 365,
                'quantity' => 5,
                'initial_quantity' => 20,
                'unit_cost' => 90.00,
                'remark' => '已过期批次，不可售',
            ],
            [
                'product_id' => $book->id,
                'batch_no' => 'BK202603-01',
                'production_date' => $today->copy()->subDays(60),
                'shelf_life_days' => 730,
                'quantity' => 120,
                'initial_quantity' => 150,
                'unit_cost' => 45.00,
                'remark' => '图书首印批次',
            ],
            [
                'product_id' => $book->id,
                'batch_no' => 'BK202604-02',
                'production_date' => $today->copy()->subDays(700),
                'shelf_life_days' => 730,
                'quantity' => 30,
                'initial_quantity' => 80,
                'unit_cost' => 43.00,
                'remark' => '再版批次，即将临期',
            ],
            [
                'product_id' => $storage->id,
                'batch_no' => 'SB202605-A',
                'production_date' => $today->copy()->subDays(15),
                'shelf_life_days' => 180,
                'quantity' => 80,
                'initial_quantity' => 100,
                'unit_cost' => 35.00,
                'remark' => '春季补货批次',
            ],
            [
                'product_id' => $storage->id,
                'batch_no' => 'SB202602-B',
                'production_date' => $today->copy()->subDays(170),
                'shelf_life_days' => 180,
                'quantity' => 20,
                'initial_quantity' => 60,
                'unit_cost' => 33.00,
                'remark' => '临期收纳箱批次',
            ],
        ];

        foreach ($batches as $batch) {
            ProductBatch::updateOrCreate(
                ['batch_no' => $batch['batch_no']],
                $batch
            );
        }
    }
}
