<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ProductBatchRepository
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ProductBatch::with(['product', 'sku']);

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['sku_id'])) {
            $query->where('sku_id', $filters['sku_id']);
        }

        if (isset($filters['batch_no']) && !empty($filters['batch_no'])) {
            $query->where('batch_no', 'like', "%{$filters['batch_no']}%");
        }

        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_sellable'])) {
            $query->where('is_sellable', (bool) $filters['is_sellable']);
        }

        if (isset($filters['has_stock']) && $filters['has_stock']) {
            $query->where('quantity', '>', 0);
        }

        if (isset($filters['expiring_soon']) && $filters['expiring_soon']) {
            $endDate = Carbon::now()->addDays(ProductBatch::EXPIRING_SOON_DAYS);
            $query->where('expiry_date', '<=', $endDate)
                  ->where('expiry_date', '>=', Carbon::now())
                  ->where('quantity', '>', 0);
        }

        if (isset($filters['expired']) && $filters['expired']) {
            $query->where('expiry_date', '<', Carbon::now());
        }

        if (isset($filters['expiry_date_start']) && !empty($filters['expiry_date_start'])) {
            $query->whereDate('expiry_date', '>=', $filters['expiry_date_start']);
        }

        if (isset($filters['expiry_date_end']) && !empty($filters['expiry_date_end'])) {
            $query->whereDate('expiry_date', '<=', $filters['expiry_date_end']);
        }

        $sortBy = $filters['sort_by'] ?? 'expiry_date';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    public function find(int $id): ?ProductBatch
    {
        return ProductBatch::with(['product', 'sku'])->find($id);
    }

    public function create(array $data): ProductBatch
    {
        return ProductBatch::create($data);
    }

    public function update(ProductBatch $batch, array $data): ProductBatch
    {
        $batch->update($data);
        return $batch->fresh();
    }

    public function delete(ProductBatch $batch): ?bool
    {
        return $batch->delete();
    }

    public function getBatchesForFifo(int $productId, ?int $skuId = null): Collection
    {
        $query = ProductBatch::where('product_id', $productId)
            ->where('is_sellable', true)
            ->where('quantity', '>', 0);

        if ($skuId) {
            $query->where('sku_id', $skuId);
        } else {
            $query->whereNull('sku_id');
        }

        return $query
            ->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getExpiringSoonBatches(int $days = 30, int $perPage = 15): LengthAwarePaginator
    {
        $endDate = Carbon::now()->addDays($days);

        return ProductBatch::with(['product', 'sku'])
            ->where('expiry_date', '<=', $endDate)
            ->where('expiry_date', '>=', Carbon::now())
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->paginate($perPage);
    }

    public function getExpiredBatches(int $perPage = 15): LengthAwarePaginator
    {
        return ProductBatch::with(['product', 'sku'])
            ->where('expiry_date', '<', Carbon::now())
            ->orderBy('expiry_date', 'asc')
            ->paginate($perPage);
    }

    public function getExpiringSummary(): array
    {
        $now = Carbon::now();
        $in7Days = $now->copy()->addDays(7);
        $in30Days = $now->copy()->addDays(30);

        return [
            'expired_count' => ProductBatch::where('expiry_date', '<', $now)
                ->where('quantity', '>', 0)
                ->count(),
            'expired_quantity' => (int) ProductBatch::where('expiry_date', '<', $now)
                ->sum('quantity'),
            'expiring_in_7_days_count' => ProductBatch::whereBetween('expiry_date', [$now, $in7Days])
                ->where('quantity', '>', 0)
                ->count(),
            'expiring_in_7_days_quantity' => (int) ProductBatch::whereBetween('expiry_date', [$now, $in7Days])
                ->sum('quantity'),
            'expiring_in_30_days_count' => ProductBatch::whereBetween('expiry_date', [$now, $in30Days])
                ->where('quantity', '>', 0)
                ->count(),
            'expiring_in_30_days_quantity' => (int) ProductBatch::whereBetween('expiry_date', [$now, $in30Days])
                ->sum('quantity'),
            'total_active_count' => ProductBatch::where('quantity', '>', 0)->count(),
            'total_active_quantity' => (int) ProductBatch::where('quantity', '>', 0)->sum('quantity'),
        ];
    }

    public function scanAndUpdateBatchStatuses(): array
    {
        $now = Carbon::now();
        $endDate = $now->copy()->addDays(ProductBatch::EXPIRING_SOON_DAYS);

        $updated = [
            'expired' => 0,
            'expiring_soon' => 0,
            'back_to_normal' => 0,
        ];

        ProductBatch::where('expiry_date', '<', $now)
            ->where('status', '!=', ProductBatch::STATUS_EXPIRED)
            ->chunkById(100, function ($batches) use (&$updated) {
                foreach ($batches as $batch) {
                    $batch->update([
                        'status' => ProductBatch::STATUS_EXPIRED,
                        'is_sellable' => false,
                    ]);
                    $updated['expired']++;
                }
            });

        ProductBatch::whereBetween('expiry_date', [$now, $endDate])
            ->where('status', '!=', ProductBatch::STATUS_EXPIRING_SOON)
            ->chunkById(100, function ($batches) use (&$updated) {
                foreach ($batches as $batch) {
                    $batch->update([
                        'status' => ProductBatch::STATUS_EXPIRING_SOON,
                        'is_sellable' => true,
                    ]);
                    $updated['expiring_soon']++;
                }
            });

        return $updated;
    }
}
