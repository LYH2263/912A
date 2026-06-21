<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('客户姓名');
            $table->string('phone', 20)->comment('手机号码');
            $table->string('address', 500)->nullable()->comment('收货地址');
            $table->unsignedInteger('order_count')->default(0)->comment('累计订单数');
            $table->decimal('total_spent', 12, 2)->default(0.00)->comment('累计消费额');
            $table->timestamps();
            $table->softDeletes();

            $table->index('phone');
            $table->index('name');
            $table->unique(['phone', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
