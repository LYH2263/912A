<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->comment('供应商名称');
            $table->string('contact', 100)->nullable()->comment('联系人');
            $table->string('phone', 50)->nullable()->comment('联系电话');
            $table->string('address', 500)->nullable()->comment('地址');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('合作状态：active-合作中，inactive-已暂停');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
