<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_batches')) {
            return;
        }

        $indexExists = collect(DB::select("SHOW INDEX FROM product_batches WHERE Key_name = 'uk_product_batch_no'"))->isNotEmpty();
        if ($indexExists) {
            return;
        }

        $duplicates = DB::table('product_batches')
            ->select('product_id', 'batch_no', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('product_id', 'batch_no')
            ->having('cnt', '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $deleteIds = [];

            foreach ($duplicates as $dup) {
                $batchIds = DB::table('product_batches')
                    ->where('product_id', $dup->product_id)
                    ->where('batch_no', $dup->batch_no)
                    ->where('id', '!=', $dup->keep_id)
                    ->pluck('id')
                    ->toArray();

                $deleteIds = array_merge($deleteIds, $batchIds);
            }

            if (!empty($deleteIds)) {
                DB::table('product_batches')
                    ->whereIn('id', $deleteIds)
                    ->delete();

                DB::table('inventory_logs')
                    ->whereIn('product_batch_id', $deleteIds)
                    ->update(['product_batch_id' => null]);
            }
        }

        Schema::table('product_batches', function (Blueprint $table) {
            $table->unique(['product_id', 'batch_no'], 'uk_product_batch_no');
        });
    }

    public function down(): void
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropUnique('uk_product_batch_no');
        });
    }
};
