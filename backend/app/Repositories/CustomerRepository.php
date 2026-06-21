<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository
{
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->fresh();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Customer::query();

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('phone', 'like', "%{$filters['search']}%")
                  ->orWhere('address', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['min_order_count'])) {
            $query->where('order_count', '>=', $filters['min_order_count']);
        }

        if (isset($filters['min_total_spent'])) {
            $query->where('total_spent', '>=', $filters['min_total_spent']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function all(array $filters = []): Collection
    {
        $query = Customer::query();

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('phone', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('name', 'asc')->get();
    }

    public function find(int $id): ?Customer
    {
        return Customer::find($id);
    }

    public function findWithOrders(int $id): ?Customer
    {
        return Customer::with(['orders' => function ($q) {
            $q->with('orderItems.product')->orderBy('created_at', 'desc');
        }])->find($id);
    }

    public function findByPhone(string $phone): ?Customer
    {
        return Customer::where('phone', $phone)->first();
    }

    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }

    public function search(string $keyword, int $limit = 20): Collection
    {
        return Customer::where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
              ->orWhere('phone', 'like', "%{$keyword}%");
        })->limit($limit)->get();
    }
}
