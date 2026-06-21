<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('sku_id')->nullable();
            $table->decimal('old_price', 10, 2);
            $table->decimal('new_price', 10, 2);
            $table->unsignedBigInteger('operator_id')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('sku_id');
            $table->index('created_at');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('sku_id')->references('id')->on('product_skus')->onDelete('set null');
            $table->foreign('operator_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};
