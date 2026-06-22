<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('inventory_logs', 'product_batch_id')) {
            return;
        }

        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('product_batch_id')->nullable()->after('sku_id')->comment('关联批次ID');

            $table->index('product_batch_id');
            $table->foreign('product_batch_id')->references('id')->on('product_batches')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_logs', function (Blueprint $table) {
            $table->dropForeign(['product_batch_id']);
            $table->dropIndex(['product_batch_id']);
            $table->dropColumn('product_batch_id');
        });
    }
};
