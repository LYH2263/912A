<?php

namespace Database\Seeders;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use Illuminate\Database\Seeder;

class PriceHistorySeeder extends Seeder
{
    public function run(): void
    {
        $targetRecords = 15;
        if (PriceHistory::query()->count() >= $targetRecords) {
            return;
        }

        $admin = User::query()->where('email', 'admin@example.com')->first();

        $records = [
            ['product_sku' => 'IPHONE15PRO001', 'old_price' => 9299.00, 'new_price' => 8999.00, 'reason' => '春季促销调价'],
            ['product_sku' => 'HUAWEIMATE60001', 'old_price' => 6299.00, 'new_price' => 5999.00, 'reason' => '竞品跟价'],
            ['product_sku' => 'MACBOOKPRO14001', 'old_price' => 15499.00, 'new_price' => 14999.00, 'reason' => '教育优惠同步'],
            ['product_sku' => 'TSHIRT001', 'old_price' => 129.00, 'new_price' => 99.00, 'reason' => '清仓特价'],
            ['product_sku' => 'BOOK_LARAVEL_10', 'old_price' => 99.00, 'new_price' => 89.00, 'reason' => '教材折扣'],
            ['product_sku' => 'KITCHEN_PAN_001', 'old_price' => 179.00, 'new_price' => 159.00, 'reason' => '供应商成本下降'],
        ];

        foreach ($records as $record) {
            $product = Product::query()->where('sku', $record['product_sku'])->first();
            if (!$product) {
                continue;
            }

            PriceHistory::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'sku_id' => null,
                    'old_price' => $record['old_price'],
                    'new_price' => $record['new_price'],
                ],
                [
                    'operator_id' => $admin?->id,
                    'reason' => $record['reason'],
                ]
            );
        }

        $skuRecords = [
            ['sku' => 'IPHONE15PRO512-NAT', 'old_price' => 11499.00, 'new_price' => 10999.00, 'reason' => '512GB 版本促销'],
            ['sku' => 'TSHIRT-GRY-XL', 'old_price' => 119.00, 'new_price' => 109.00, 'reason' => 'XL 码特价'],
            ['sku' => 'MATE60-512-PP', 'old_price' => 7299.00, 'new_price' => 6999.00, 'reason' => '512GB 南糯紫降价'],
        ];

        foreach ($skuRecords as $record) {
            $sku = ProductSku::query()->where('sku', $record['sku'])->first();
            if (!$sku) {
                continue;
            }

            PriceHistory::updateOrCreate(
                [
                    'product_id' => $sku->product_id,
                    'sku_id' => $sku->id,
                    'old_price' => $record['old_price'],
                    'new_price' => $record['new_price'],
                ],
                [
                    'operator_id' => $admin?->id,
                    'reason' => $record['reason'],
                ]
            );
        }
    }
}
