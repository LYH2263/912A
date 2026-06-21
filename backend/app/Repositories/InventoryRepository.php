<?php

namespace App\Repositories;

use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InventoryRepository
{
    /**
     * 获取库存列表（分页）
     * 
     * 库存状态标准：
     * - 缺货：stock_quantity = 0
     * - 低库存：0 < stock_quantity <= 10
     * - 充足：stock_quantity > 10
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::with(['category', 'skus'])
            ->withCount('skus as sku_count')
            ->withSum('skus as total_stock', 'stock_quantity')
            ->withMin('skus as min_price', 'price')
            ->withMax('skus as max_price', 'price');

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhereHas('skus', function ($sq) use ($search) {
                      $sq->where('sku', 'like', "%{$search}%");
                  });
            });
        }

        if (isset($filters['out_of_stock'])) {
            $query->where('stock_quantity', 0);
        } elseif (isset($filters['low_stock'])) {
            $query->where('stock_quantity', '>', 0)
                  ->where('stock_quantity', '<=', 10);
        } elseif (isset($filters['sufficient'])) {
            $query->where('stock_quantity', '>', 10);
        }

        return $query->orderBy('stock_quantity', 'asc')->paginate($perPage);
    }

    /**
     * 获取库存变动记录
     */
    public function getLogs(int $productId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = InventoryLog::with('operator', 'relatedOrder')
            ->where('product_id', $productId);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
