<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository
{
    /**
     * 创建订单
     */
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    /**
     * 更新订单
     */
    public function update(Order $order, array $data): Order
    {
        $order->update($data);
        return $order->fresh();
    }

    /**
     * 获取订单列表（分页）
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::with('orderItems.product');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['order_no'])) {
            $query->where('order_no', 'like', "%{$filters['order_no']}%");
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * 根据ID获取订单
     */
    public function find(int $id): ?Order
    {
        return Order::with('orderItems.product')->find($id);
    }
}
