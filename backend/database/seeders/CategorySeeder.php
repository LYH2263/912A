<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $electronics = Category::updateOrCreate(
            ['name' => '电子产品', 'parent_id' => null],
            [
                'description' => '各类电子产品',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        Category::updateOrCreate(
            ['name' => '手机', 'parent_id' => $electronics->id],
            [
                'description' => '智能手机',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        Category::updateOrCreate(
            ['name' => '电脑', 'parent_id' => $electronics->id],
            [
                'description' => '笔记本电脑和台式机',
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        $clothing = Category::updateOrCreate(
            ['name' => '服装', 'parent_id' => null],
            [
                'description' => '各类服装',
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        Category::updateOrCreate(
            ['name' => '男装', 'parent_id' => $clothing->id],
            [
                'description' => '男士服装',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        Category::updateOrCreate(
            ['name' => '女装', 'parent_id' => $clothing->id],
            [
                'description' => '女士服装',
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        $home = Category::updateOrCreate(
            ['name' => '家居生活', 'parent_id' => null],
            [
                'description' => '家居日用、清洁收纳',
                'sort_order' => 3,
                'is_active' => true,
            ]
        );

        Category::updateOrCreate(
            ['name' => '厨房用品', 'parent_id' => $home->id],
            [
                'description' => '锅具、餐具、收纳',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        Category::updateOrCreate(
            ['name' => '清洁收纳', 'parent_id' => $home->id],
            [
                'description' => '清洁、收纳、整理',
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        $books = Category::updateOrCreate(
            ['name' => '图书', 'parent_id' => null],
            [
                'description' => '技术、文学、经济管理',
                'sort_order' => 4,
                'is_active' => true,
            ]
        );

        Category::updateOrCreate(
            ['name' => '技术', 'parent_id' => $books->id],
            [
                'description' => '编程与工程实践',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        Category::updateOrCreate(
            ['name' => '文学', 'parent_id' => $books->id],
            [
                'description' => '小说、散文、诗歌',
                'sort_order' => 2,
                'is_active' => true,
            ]
        );
    }
}
