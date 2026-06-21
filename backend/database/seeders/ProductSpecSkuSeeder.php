<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Database\Seeder;

class ProductSpecSkuSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            'IPHONE15PRO001' => [
                'specs' => [
                    ['name' => '存储', 'values' => ['256GB', '512GB']],
                    ['name' => '颜色', 'values' => ['原色钛金属', '黑色钛金属']],
                ],
                'skus' => [
                    ['sku' => 'IPHONE15PRO256-NAT', 'spec_data' => ['存储' => '256GB', '颜色' => '原色钛金属'], 'price' => 8999.00, 'cost_price' => 7500.00, 'stock_quantity' => 25],
                    ['sku' => 'IPHONE15PRO512-NAT', 'spec_data' => ['存储' => '512GB', '颜色' => '原色钛金属'], 'price' => 10999.00, 'cost_price' => 9200.00, 'stock_quantity' => 15],
                    ['sku' => 'IPHONE15PRO256-BLK', 'spec_data' => ['存储' => '256GB', '颜色' => '黑色钛金属'], 'price' => 8999.00, 'cost_price' => 7500.00, 'stock_quantity' => 10],
                ],
            ],
            'TSHIRT001' => [
                'specs' => [
                    ['name' => '颜色', 'values' => ['白色', '黑色', '灰色']],
                    ['name' => '尺码', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'skus' => [
                    ['sku' => 'TSHIRT-WHT-M', 'spec_data' => ['颜色' => '白色', '尺码' => 'M'], 'price' => 99.00, 'cost_price' => 50.00, 'stock_quantity' => 50],
                    ['sku' => 'TSHIRT-BLK-L', 'spec_data' => ['颜色' => '黑色', '尺码' => 'L'], 'price' => 99.00, 'cost_price' => 50.00, 'stock_quantity' => 60],
                    ['sku' => 'TSHIRT-GRY-XL', 'spec_data' => ['颜色' => '灰色', '尺码' => 'XL'], 'price' => 109.00, 'cost_price' => 55.00, 'stock_quantity' => 40],
                ],
            ],
            'DRESS_W_001' => [
                'specs' => [
                    ['name' => '款式', 'values' => ['碎花', '纯色']],
                    ['name' => '尺码', 'values' => ['S', 'M', 'L']],
                ],
                'skus' => [
                    ['sku' => 'DRESS-FLR-S', 'spec_data' => ['款式' => '碎花', '尺码' => 'S'], 'price' => 359.00, 'cost_price' => 210.00, 'stock_quantity' => 20],
                    ['sku' => 'DRESS-FLR-M', 'spec_data' => ['款式' => '碎花', '尺码' => 'M'], 'price' => 359.00, 'cost_price' => 210.00, 'stock_quantity' => 25],
                    ['sku' => 'DRESS-SLD-L', 'spec_data' => ['款式' => '纯色', '尺码' => 'L'], 'price' => 329.00, 'cost_price' => 190.00, 'stock_quantity' => 20],
                ],
            ],
            'HUAWEIMATE60001' => [
                'specs' => [
                    ['name' => '存储', 'values' => ['256GB', '512GB']],
                    ['name' => '颜色', 'values' => ['雅川青', '南糯紫']],
                ],
                'skus' => [
                    ['sku' => 'MATE60-256-GN', 'spec_data' => ['存储' => '256GB', '颜色' => '雅川青'], 'price' => 5999.00, 'cost_price' => 5000.00, 'stock_quantity' => 20],
                    ['sku' => 'MATE60-512-PP', 'spec_data' => ['存储' => '512GB', '颜色' => '南糯紫'], 'price' => 6999.00, 'cost_price' => 5800.00, 'stock_quantity' => 15],
                ],
            ],
        ];

        foreach ($definitions as $productSku => $definition) {
            $product = Product::query()->where('sku', $productSku)->first();
            if (!$product) {
                continue;
            }

            if (!$product->specs()->exists()) {
                foreach ($definition['specs'] as $specIndex => $spec) {
                    $productSpec = $product->specs()->create([
                        'name' => $spec['name'],
                        'sort' => $specIndex,
                    ]);

                    foreach ($spec['values'] as $valueIndex => $value) {
                        $productSpec->values()->create([
                            'value' => $value,
                            'sort' => $valueIndex,
                        ]);
                    }
                }
            }

            foreach ($definition['skus'] as $skuData) {
                ProductSku::updateOrCreate(
                    ['sku' => $skuData['sku']],
                    [
                        'product_id' => $product->id,
                        'price' => $skuData['price'],
                        'cost_price' => $skuData['cost_price'],
                        'stock_quantity' => $skuData['stock_quantity'],
                        'spec_data' => $skuData['spec_data'],
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
