<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Supplier;
use App\Repositories\SupplierRepository;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    public function __construct(
        private SupplierRepository $repository
    ) {
    }

    public function create(array $data): Supplier
    {
        return $this->repository->create($data);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        return $this->repository->update($supplier, $data);
    }

    public function delete(Supplier $supplier): bool
    {
        return DB::transaction(function () use ($supplier) {
            Product::where('supplier_id', $supplier->id)->update(['supplier_id' => null]);

            if ($supplier->products()->exists()) {
                return $supplier->delete();
            }
            return $supplier->forceDelete();
        });
    }

    public function toggleStatus(Supplier $supplier): Supplier
    {
        $status = $supplier->status === 'active' ? 'inactive' : 'active';
        return $this->repository->update($supplier, ['status' => $status]);
    }

    public function getProductCount(int $supplierId): int
    {
        return $this->repository->getProductCount($supplierId);
    }
}
