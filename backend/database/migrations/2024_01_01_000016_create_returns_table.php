<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no', 50)->unique();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_item_id');
            $table->enum('type', ['return', 'exchange'])->comment('类型: return-退货, exchange-换货');
            $table->text('reason')->comment('退换货原因');
            $table->decimal('refund_amount', 10, 2)->default(0.00)->comment('退款金额');
            $table->integer('quantity')->default(1)->comment('退货数量');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending')->comment('状态: pending-待审核, approved-已通过, rejected-已拒绝, completed-已完成');
            $table->text('reject_reason')->nullable()->comment('拒绝原因');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作人ID');
            $table->timestamps();

            $table->index('order_id');
            $table->index('order_item_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};
