<?php

namespace App\Repositories;

use App\Models\ReturnRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReturnRepository
{
    public function create(array $data): ReturnRequest
    {
        return ReturnRequest::create($data);
    }

    public function update(ReturnRequest $return, array $data): ReturnRequest
    {
        $return->update($data);
        return $return->fresh();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ReturnRequest::with(['order', 'orderItem.product', 'operator']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['return_no'])) {
            $query->where('return_no', 'like', "%{$filters['return_no']}%");
        }

        if (isset($filters['order_no'])) {
            $query->whereHas('order', function ($q) use ($filters) {
                $q->where('order_no', 'like', "%{$filters['order_no']}%");
            });
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function find(int $id): ?ReturnRequest
    {
        return ReturnRequest::with(['order', 'orderItem.product', 'orderItem.sku', 'operator'])->find($id);
    }
}
