<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_batches')) {
            return;
        }

        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('sku_id')->nullable();
            $table->string('batch_no', 100)->comment('批次号');
            $table->date('production_date')->comment('生产日期');
            $table->unsignedInteger('shelf_life_days')->comment('保质期天数');
            $table->date('expiry_date')->comment('到期日');
            $table->unsignedInteger('quantity')->default(0)->comment('批次当前库存');
            $table->unsignedInteger('initial_quantity')->default(0)->comment('批次初始入库数量');
            $table->unsignedDecimal('unit_cost', 10, 2)->nullable()->comment('入库单价成本');
            $table->enum('status', ['normal', 'expiring_soon', 'expired'])->default('normal')->comment('批次状态：normal-正常，expiring_soon-临期，expired-已过期');
            $table->boolean('is_sellable')->default(true)->comment('是否可售');
            $table->text('remark')->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('sku_id');
            $table->index('batch_no');
            $table->index('expiry_date');
            $table->index('status');
            $table->index(['product_id', 'status']);
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('sku_id')->references('id')->on('product_skus')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
