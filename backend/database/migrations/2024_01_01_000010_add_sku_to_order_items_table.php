<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_sku_id')->nullable()->after('product_id');
            $table->json('spec_snapshot')->nullable()->after('product_sku');

            $table->index('product_sku_id');
            $table->foreign('product_sku_id')->references('id')->on('product_skus')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_sku_id']);
            $table->dropIndex(['product_sku_id']);
            $table->dropColumn('product_sku_id');
            $table->dropColumn('spec_snapshot');
        });
    }
};
