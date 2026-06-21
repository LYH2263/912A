<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['name' => '热销', 'color' => '#F56C6C', 'sort_order' => 1],
            ['name' => '新品', 'color' => '#409EFF', 'sort_order' => 2],
            ['name' => '促销', 'color' => '#E6A23C', 'sort_order' => 3],
            ['name' => '精品', 'color' => '#67C23A', 'sort_order' => 4],
            ['name' => '临期特惠', 'color' => '#909399', 'sort_order' => 5],
        ];

        $tagModels = [];
        foreach ($tags as $tag) {
            $tagModels[$tag['name']] = Tag::updateOrCreate(
                ['name' => $tag['name']],
                $tag
            );
        }

        $productTags = [
            'IPHONE15PRO001' => ['热销', '新品', '精品'],
            'HUAWEIMATE60001' => ['热销', '精品'],
            'XIAOMI14PRO001' => ['新品', '促销'],
            'MACBOOKPRO14001' => ['精品', '热销'],
            'TSHIRT001' => ['促销', '热销'],
            'DRESS_W_001' => ['新品', '热销'],
            'KITCHEN_PAN_001' => ['促销'],
            'BOOK_LARAVEL_10' => ['精品'],
            'BOOK_VUE3' => ['新品', '精品'],
        ];

        foreach ($productTags as $sku => $tagNames) {
            $product = Product::query()->where('sku', $sku)->first();
            if (!$product) {
                continue;
            }

            $ids = collect($tagNames)
                ->map(fn ($name) => $tagModels[$name]->id ?? null)
                ->filter()
                ->values()
                ->all();

            if (!empty($ids)) {
                $product->tags()->syncWithoutDetaching($ids);
            }
        }
    }
}
