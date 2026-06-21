<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_specs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('name', 50);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('product_spec_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spec_id');
            $table->string('value', 100);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index('spec_id');
            $table->foreign('spec_id')->references('id')->on('product_specs')->onDelete('cascade');
        });

        Schema::create('product_skus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('sku', 100)->unique();
            $table->decimal('price', 10, 2);
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->string('image', 255)->nullable();
            $table->json('spec_data')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('product_id');
            $table->index('sku');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_skus');
        Schema::dropIfExists('product_spec_values');
        Schema::dropIfExists('product_specs');
    }
};
