<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $targetReviews = 20;
        if (Review::query()->count() >= $targetReviews) {
            return;
        }

        $admin = User::query()->where('email', 'admin@example.com')->first();

        $reviews = [
            ['product_sku' => 'IPHONE15PRO001', 'reviewer_name' => '数码爱好者', 'rating' => 5, 'content' => '拍照效果出色，系统流畅，续航比上一代有明显提升。', 'status' => 'approved'],
            ['product_sku' => 'IPHONE15PRO001', 'reviewer_name' => '小明', 'rating' => 4, 'content' => '整体不错，就是价格偏高，希望有更多优惠。', 'status' => 'approved'],
            ['product_sku' => 'HUAWEIMATE60001', 'reviewer_name' => '花粉', 'rating' => 5, 'content' => '信号和续航都很满意，国产旗舰实至名归。', 'status' => 'approved'],
            ['product_sku' => 'MACBOOKPRO14001', 'reviewer_name' => '程序员阿杰', 'rating' => 5, 'content' => 'M3 芯片编译项目速度飞快，屏幕素质一流。', 'status' => 'approved'],
            ['product_sku' => 'TSHIRT001', 'reviewer_name' => '买家1688', 'rating' => 4, 'content' => '纯棉手感舒适，尺码标准，洗后略有缩水。', 'status' => 'approved'],
            ['product_sku' => 'DRESS_W_001', 'reviewer_name' => '时尚达人', 'rating' => 5, 'content' => '碎花款式很显气质，面料轻薄适合夏天。', 'status' => 'pending'],
            ['product_sku' => 'KITCHEN_PAN_001', 'reviewer_name' => '居家小能手', 'rating' => 4, 'content' => '不粘效果可以，就是手柄有点烫手。', 'status' => 'approved'],
            ['product_sku' => 'BOOK_LARAVEL_10', 'reviewer_name' => '后端新手', 'rating' => 5, 'content' => '案例丰富，适合 Laravel 入门到进阶。', 'status' => 'approved'],
            ['product_sku' => 'BOOK_VUE3', 'reviewer_name' => '前端小王', 'rating' => 4, 'content' => '内容全面，部分章节可以再深入一些。', 'status' => 'pending'],
            ['product_sku' => 'XIAOMI14PRO001', 'reviewer_name' => '米粉', 'rating' => 3, 'content' => '性价比可以，但发热比预期明显。', 'status' => 'rejected'],
            ['product_sku' => 'STORAGE_BOX_001', 'reviewer_name' => '收纳控', 'rating' => 5, 'content' => '容量大且可叠加，整理衣柜很实用。', 'status' => 'approved'],
            ['product_sku' => 'THINKPADX1001', 'reviewer_name' => '商务人士', 'rating' => 4, 'content' => '键盘手感好，出差携带方便。', 'status' => 'hidden'],
            ['product_sku' => 'BOOK_NOVEL_001', 'reviewer_name' => '书虫', 'rating' => 5, 'content' => '情节紧凑，一口气读完，强烈推荐。', 'status' => 'approved'],
            ['product_sku' => 'DELLXPS13001', 'reviewer_name' => '学生党', 'rating' => 4, 'content' => '轻薄便携，日常办公够用。', 'status' => 'pending'],
        ];

        foreach ($reviews as $reviewData) {
            $product = Product::query()->where('sku', $reviewData['product_sku'])->first();
            if (!$product) {
                continue;
            }

            $review = Review::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'reviewer_name' => $reviewData['reviewer_name'],
                    'content' => $reviewData['content'],
                ],
                [
                    'user_id' => null,
                    'rating' => $reviewData['rating'],
                    'status' => $reviewData['status'],
                    'reviewed_by' => in_array($reviewData['status'], ['approved', 'rejected', 'hidden']) ? $admin?->id : null,
                    'reviewed_at' => in_array($reviewData['status'], ['approved', 'rejected', 'hidden']) ? now()->subDays(rand(1, 10)) : null,
                ]
            );
        }
    }
}
