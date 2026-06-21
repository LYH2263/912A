<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('reviewer_name', 100)->nullable()->comment('评价人名称（代录时使用）');
            $table->unsignedTinyInteger('rating')->comment('评分 1-5 星');
            $table->text('content')->nullable()->comment('评价文字内容');
            $table->enum('status', ['pending', 'approved', 'rejected', 'hidden'])->default('pending')->comment('状态：待审核、已通过、已拒绝、已隐藏');
            $table->unsignedBigInteger('reviewed_by')->nullable()->comment('审核人ID');
            $table->timestamp('reviewed_at')->nullable()->comment('审核时间');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['product_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
