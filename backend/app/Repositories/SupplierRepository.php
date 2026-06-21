<?php

namespace App\Repositories;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SupplierRepository
{
    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);
        return $supplier->fresh();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Supplier::withCount('products as product_count');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('contact', 'like', "%{$filters['search']}%")
                  ->orWhere('phone', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function all(array $filters = []): Collection
    {
        $query = Supplier::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('name', 'asc')->get();
    }

    public function find(int $id): ?Supplier
    {
        return Supplier::find($id);
    }

    public function findWithProducts(int $id): ?Supplier
    {
        return Supplier::withCount('products as product_count')->find($id);
    }

    public function delete(Supplier $supplier): bool
    {
        return $supplier->delete();
    }

    public function getProductCount(int $supplierId): int
    {
        return Supplier::find($supplierId)?->products()->count() ?? 0;
    }
}
