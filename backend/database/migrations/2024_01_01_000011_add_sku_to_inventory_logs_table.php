<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('sku_id')->nullable()->after('product_id');
            $table->index('sku_id');
            $table->foreign('sku_id')->references('id')->on('product_skus')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->dropForeign(['sku_id']);
            $table->dropIndex(['sku_id']);
            $table->dropColumn('sku_id');
        });
    }
};
