<?php

namespace App\Repositories;

use App\Models\Coupon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CouponRepository
{
    public function create(array $data): Coupon
    {
        return Coupon::create($data);
    }

    public function update(Coupon $coupon, array $data): Coupon
    {
        $coupon->update($data);
        return $coupon->fresh();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Coupon::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function find(int $id): ?Coupon
    {
        return Coupon::find($id);
    }

    public function findByCode(string $code): ?Coupon
    {
        return Coupon::where('code', $code)->first();
    }

    public function getAvailableForAmount(float $amount): \Illuminate\Database\Eloquent\Collection
    {
        return Coupon::valid()
            ->where('min_amount', '<=', $amount)
            ->orderByDesc('value')
            ->get();
    }
}
