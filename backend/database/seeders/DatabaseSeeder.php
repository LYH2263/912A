<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            SupplierSeeder::class,
            TagSeeder::class,
            ProductSeeder::class,
            ProductSpecSkuSeeder::class,
            CouponSeeder::class,
            CustomerSeeder::class,
            ProductBatchSeeder::class,
            PriceHistorySeeder::class,
            OrderSeeder::class,
            ReturnSeeder::class,
            ReviewSeeder::class,
            LowStockAlertSeeder::class,
            InventoryLogSeeder::class,
        ]);
    }
}
