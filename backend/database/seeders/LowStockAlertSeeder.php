<?php

namespace Database\Seeders;

use App\Models\LowStockAlert;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class LowStockAlertSeeder extends Seeder
{
    public function run(): void
    {
        $targetAlerts = 6;
        if (LowStockAlert::query()->count() >= $targetAlerts) {
            return;
        }

        $admin = User::query()->where('email', 'admin@example.com')->first();

        $lowStockProducts = [
            ['sku' => 'DELLXPS13001', 'stock_quantity' => 3],
            ['sku' => 'THINKPADX1001', 'stock_quantity' => 4],
            ['sku' => 'JACKET_M_001', 'stock_quantity' => 8],
        ];

        foreach ($lowStockProducts as $item) {
            $product = Product::query()->where('sku', $item['sku'])->first();
            if (!$product) {
                continue;
            }

            $product->update(['stock_quantity' => $item['stock_quantity']]);

            LowStockAlert::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'status' => 'unread',
                ],
                [
                    'current_stock' => $item['stock_quantity'],
                    'threshold' => $product->low_stock_threshold,
                ]
            );
        }

        $readProduct = Product::query()->where('sku', 'BOOK_NOVEL_001')->first();
        if ($readProduct && $readProduct->stock_quantity <= $readProduct->low_stock_threshold) {
            LowStockAlert::firstOrCreate(
                [
                    'product_id' => $readProduct->id,
                    'status' => 'read',
                ],
                [
                    'current_stock' => $readProduct->stock_quantity,
                    'threshold' => $readProduct->low_stock_threshold,
                    'read_at' => now()->subDays(2),
                    'read_by' => $admin?->id,
                ]
            );
        } elseif ($readProduct) {
            $readProduct->update(['stock_quantity' => 12]);
            LowStockAlert::firstOrCreate(
                [
                    'product_id' => $readProduct->id,
                    'status' => 'read',
                ],
                [
                    'current_stock' => 12,
                    'threshold' => $readProduct->low_stock_threshold,
                    'read_at' => now()->subDays(2),
                    'read_by' => $admin?->id,
                ]
            );
        }
    }
}
