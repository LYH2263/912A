<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('coupon_user');

        Schema::create('coupon_customer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedInteger('times_used')->default(1);
            $table->timestamps();

            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->unique(['coupon_id', 'customer_id']);

            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_customer');

        Schema::create('coupon_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('times_used')->default(1);
            $table->timestamps();

            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['coupon_id', 'user_id']);
        });
    }
};
