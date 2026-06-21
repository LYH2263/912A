<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::query()->pluck('id', 'name');

        $items = [
            // 手机
            [
                'sku' => 'IPHONE15PRO001',
                'name' => 'iPhone 15 Pro',
                'category' => '手机',
                'description' => 'Apple iPhone 15 Pro 256GB',
                'price' => 8999.00,
                'cost_price' => 7500.00,
                'stock_quantity' => 50,
                'low_stock_threshold' => 10,
                'weight' => 0.187,
            ],
            [
                'sku' => 'HUAWEIMATE60001',
                'name' => '华为 Mate 60',
                'category' => '手机',
                'description' => '华为 Mate 60 256GB',
                'price' => 5999.00,
                'cost_price' => 5000.00,
                'stock_quantity' => 35,
                'low_stock_threshold' => 10,
                'weight' => 0.225,
            ],
            [
                'sku' => 'XIAOMI14PRO001',
                'name' => '小米 14 Pro',
                'category' => '手机',
                'description' => '小米 14 Pro 512GB',
                'price' => 5299.00,
                'cost_price' => 4200.00,
                'stock_quantity' => 60,
                'low_stock_threshold' => 15,
                'weight' => 0.210,
            ],

            // 电脑
            [
                'sku' => 'MACBOOKPRO14001',
                'name' => 'MacBook Pro 14',
                'category' => '电脑',
                'description' => 'Apple MacBook Pro 14英寸 M3芯片',
                'price' => 14999.00,
                'cost_price' => 12000.00,
                'stock_quantity' => 30,
                'low_stock_threshold' => 5,
                'weight' => 1.6,
            ],
            [
                'sku' => 'THINKPADX1001',
                'name' => '联想 ThinkPad X1',
                'category' => '电脑',
                'description' => '联想 ThinkPad X1 Carbon',
                'price' => 12999.00,
                'cost_price' => 10000.00,
                'stock_quantity' => 18,
                'low_stock_threshold' => 5,
                'weight' => 1.13,
            ],
            [
                'sku' => 'DELLXPS13001',
                'name' => 'Dell XPS 13',
                'category' => '电脑',
                'description' => 'Dell XPS 13 轻薄本',
                'price' => 9999.00,
                'cost_price' => 8200.00,
                'stock_quantity' => 22,
                'low_stock_threshold' => 5,
                'weight' => 1.17,
            ],

            // 服装
            [
                'sku' => 'TSHIRT001',
                'name' => '男士T恤',
                'category' => '男装',
                'description' => '纯棉男士T恤',
                'price' => 99.00,
                'cost_price' => 50.00,
                'stock_quantity' => 200,
                'low_stock_threshold' => 50,
                'weight' => 0.2,
            ],
            [
                'sku' => 'JACKET_M_001',
                'name' => '男士夹克',
                'category' => '男装',
                'description' => '春秋休闲夹克',
                'price' => 299.00,
                'cost_price' => 180.00,
                'stock_quantity' => 80,
                'low_stock_threshold' => 20,
                'weight' => 0.7,
            ],
            [
                'sku' => 'DRESS_W_001',
                'name' => '女士连衣裙',
                'category' => '女装',
                'description' => '法式碎花连衣裙',
                'price' => 359.00,
                'cost_price' => 210.00,
                'stock_quantity' => 65,
                'low_stock_threshold' => 15,
                'weight' => 0.5,
            ],

            // 家居
            [
                'sku' => 'KITCHEN_PAN_001',
                'name' => '不粘锅 28cm',
                'category' => '厨房用品',
                'description' => '家用不粘锅，适用燃气灶/电磁炉',
                'price' => 159.00,
                'cost_price' => 95.00,
                'stock_quantity' => 120,
                'low_stock_threshold' => 20,
                'weight' => 1.2,
            ],
            [
                'sku' => 'STORAGE_BOX_001',
                'name' => '收纳箱 50L',
                'category' => '清洁收纳',
                'description' => '透明可叠加收纳箱',
                'price' => 69.00,
                'cost_price' => 35.00,
                'stock_quantity' => 160,
                'low_stock_threshold' => 30,
                'weight' => 1.0,
            ],

            // 图书
            [
                'sku' => 'BOOK_LARAVEL_10',
                'name' => 'Laravel 10 实战',
                'category' => '技术',
                'description' => 'Laravel 10 从入门到实战',
                'price' => 89.00,
                'cost_price' => 45.00,
                'stock_quantity' => 240,
                'low_stock_threshold' => 40,
                'weight' => 0.6,
            ],
            [
                'sku' => 'BOOK_VUE3',
                'name' => 'Vue 3 进阶指南',
                'category' => '技术',
                'description' => 'Vue 3 + Vite + 工程化实践',
                'price' => 79.00,
                'cost_price' => 40.00,
                'stock_quantity' => 180,
                'low_stock_threshold' => 30,
                'weight' => 0.55,
            ],
            [
                'sku' => 'BOOK_NOVEL_001',
                'name' => '长夜难明',
                'category' => '文学',
                'description' => '热门推理小说',
                'price' => 49.00,
                'cost_price' => 22.00,
                'stock_quantity' => 95,
                'low_stock_threshold' => 15,
                'weight' => 0.4,
            ],
        ];

        foreach ($items as $item) {
            $categoryId = $categories[$item['category']] ?? null;

            Product::updateOrCreate(
                ['sku' => $item['sku']],
                [
                    'name' => $item['name'],
                    'category_id' => $categoryId,
                    'description' => $item['description'],
                    'price' => $item['price'],
                    'cost_price' => $item['cost_price'],
                    'status' => 'active',
                    'stock_quantity' => $item['stock_quantity'],
                    'low_stock_threshold' => $item['low_stock_threshold'],
                    'weight' => $item['weight'],
                ]
            );
        }
    }
}
