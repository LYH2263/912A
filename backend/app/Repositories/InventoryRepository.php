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
        $query = Product::with('category');

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // 缺货：库存为0
        if (isset($filters['out_of_stock'])) {
            $query->where('stock_quantity', 0);
        }
        // 低库存：库存大于0且小于等于10
        elseif (isset($filters['low_stock'])) {
            $query->where('stock_quantity', '>', 0)
                  ->where('stock_quantity', '<=', 10);
        }
        // 充足：库存大于10
        elseif (isset($filters['sufficient'])) {
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
