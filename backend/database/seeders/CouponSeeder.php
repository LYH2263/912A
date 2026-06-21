<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME50',
                'name' => '新客立减50元',
                'type' => 'fixed',
                'value' => 50.00,
                'min_amount' => 200.00,
                'total_quantity' => 500,
                'used_quantity' => 12,
                'per_user_limit' => 1,
                'starts_at' => now()->subMonths(2),
                'expires_at' => now()->addMonths(3),
                'status' => 'active',
            ],
            [
                'code' => 'SAVE10',
                'name' => '全场9折券',
                'type' => 'percent',
                'value' => 10.00,
                'min_amount' => 100.00,
                'total_quantity' => 1000,
                'used_quantity' => 35,
                'per_user_limit' => 2,
                'starts_at' => now()->subMonth(),
                'expires_at' => now()->addMonths(2),
                'status' => 'active',
            ],
            [
                'code' => 'VIP100',
                'name' => '满500减100',
                'type' => 'fixed',
                'value' => 100.00,
                'min_amount' => 500.00,
                'total_quantity' => 200,
                'used_quantity' => 8,
                'per_user_limit' => 1,
                'starts_at' => now()->subWeeks(2),
                'expires_at' => now()->addMonth(),
                'status' => 'active',
            ],
            [
                'code' => 'PHONE200',
                'name' => '手机专享200元券',
                'type' => 'fixed',
                'value' => 200.00,
                'min_amount' => 3000.00,
                'total_quantity' => 100,
                'used_quantity' => 5,
                'per_user_limit' => 1,
                'starts_at' => now()->subDays(10),
                'expires_at' => now()->addDays(20),
                'status' => 'active',
            ],
            [
                'code' => 'EXPIRED2024',
                'name' => '2024年终券（已过期）',
                'type' => 'fixed',
                'value' => 30.00,
                'min_amount' => 150.00,
                'total_quantity' => 300,
                'used_quantity' => 300,
                'per_user_limit' => 1,
                'starts_at' => now()->subYear(),
                'expires_at' => now()->subMonths(3),
                'status' => 'active',
            ],
            [
                'code' => 'OFFLINE99',
                'name' => '已下架测试券',
                'type' => 'percent',
                'value' => 5.00,
                'min_amount' => 50.00,
                'total_quantity' => 100,
                'used_quantity' => 0,
                'per_user_limit' => 1,
                'starts_at' => now()->subMonth(),
                'expires_at' => now()->addMonth(),
                'status' => 'inactive',
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                $coupon
            );
        }
    }
}
