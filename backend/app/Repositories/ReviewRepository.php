<?php

namespace App\Repositories;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ReviewRepository
{
    public function create(array $data): Review
    {
        return Review::create($data);
    }

    public function update(Review $review, array $data): Review
    {
        $review->update($data);
        return $review->fresh();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Review::with(['product:id,name,sku', 'user:id,name,email', 'reviewer:id,name,email']);

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        if (isset($filters['min_rating'])) {
            $query->where('rating', '>=', $filters['min_rating']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('content', 'like', "%{$filters['search']}%")
                  ->orWhere('reviewer_name', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function find(int $id): ?Review
    {
        return Review::with(['product:id,name,sku', 'user:id,name,email', 'reviewer:id,name,email'])->find($id);
    }

    public function getApprovedByProduct(int $productId, int $perPage = 10): LengthAwarePaginator
    {
        return Review::with(['user:id,name,email'])
            ->where('product_id', $productId)
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getProductSummary(int $productId): array
    {
        $reviews = Review::where('product_id', $productId)
            ->where('status', 'approved');

        $totalCount = (clone $reviews)->count();
        $avgRating = $totalCount > 0 ? round((clone $reviews)->avg('rating'), 2) : 0;

        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = (clone $reviews)->where('rating', $i)->count();
            $distribution[$i] = [
                'count' => $count,
                'percent' => $totalCount > 0 ? round(($count / $totalCount) * 100, 1) : 0,
            ];
        }

        return [
            'product_id' => $productId,
            'total_count' => $totalCount,
            'avg_rating' => $avgRating,
            'distribution' => $distribution,
        ];
    }

    public function getAllProductsSummary(): Collection
    {
        return Review::select(
            'product_id',
            DB::raw('COUNT(*) as total_count'),
            DB::raw('ROUND(AVG(rating), 2) as avg_rating')
        )
            ->where('status', 'approved')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');
    }

    public function getStatistics(): array
    {
        $total = Review::count();
        $pending = Review::where('status', 'pending')->count();
        $approved = Review::where('status', 'approved')->count();
        $rejected = Review::where('status', 'rejected')->count();
        $hidden = Review::where('status', 'hidden')->count();
        $avgRating = Review::where('status', 'approved')->avg('rating');

        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'hidden' => $hidden,
            'avg_rating' => $avgRating ? round($avgRating, 2) : 0,
        ];
    }
}
