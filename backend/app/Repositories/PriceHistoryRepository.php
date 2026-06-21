<?php

namespace App\Repositories;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PriceHistoryRepository
{
    public function create(array $data): PriceHistory
    {
        return PriceHistory::create($data);
    }

    public function getByProductId(int $productId, int $perPage = 15): LengthAwarePaginator
    {
        return PriceHistory::with(['operator', 'sku'])
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getAllByProductId(int $productId): Collection
    {
        return PriceHistory::with(['operator', 'sku'])
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getTrendData(int $productId, int $days = 90): Collection
    {
        $startDate = now()->subDays($days);

        return PriceHistory::where('product_id', $productId)
            ->where('sku_id', null)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getSkuTrendData(int $skuId, int $days = 90): Collection
    {
        $startDate = now()->subDays($days);

        return PriceHistory::where('sku_id', $skuId)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
