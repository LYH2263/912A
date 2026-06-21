<?php

namespace App\Services;

use App\Models\Review;
use App\Models\User;
use App\Repositories\ReviewRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewService
{
    public function __construct(
        private ReviewRepository $repository
    ) {
    }

    public function create(array $data, ?User $operator = null): Review
    {
        $this->validateReviewData($data);

        return DB::transaction(function () use ($data, $operator) {
            if (empty($data['reviewer_name']) && $operator) {
                $data['reviewer_name'] = $operator->name ?: $operator->email;
            }

            if (isset($data['status']) && $data['status'] === 'approved' && $operator) {
                $data['reviewed_by'] = $operator->id;
                $data['reviewed_at'] = now();
            }

            $review = $this->repository->create($data);

            Log::info('评价创建', [
                'review_id' => $review->id,
                'product_id' => $review->product_id,
                'rating' => $review->rating,
                'operator_id' => $operator?->id,
            ]);

            return $this->repository->find($review->id);
        });
    }

    public function update(Review $review, array $data, ?User $operator = null): Review
    {
        if (isset($data['rating'])) {
            $this->validateReviewData($data);
        }

        return DB::transaction(function () use ($review, $data, $operator) {
            if (isset($data['status'])) {
                $oldStatus = $review->status;
                $newStatus = $data['status'];

                if ($oldStatus !== $newStatus && in_array($newStatus, ['approved', 'rejected']) && $operator) {
                    $data['reviewed_by'] = $operator->id;
                    $data['reviewed_at'] = now();
                }

                Log::info('评价状态变更', [
                    'review_id' => $review->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'operator_id' => $operator?->id,
                ]);
            }

            return $this->repository->update($review, $data);
        });
    }

    public function delete(Review $review, ?User $operator = null): bool
    {
        DB::transaction(function () use ($review, $operator) {
            Log::info('评价删除', [
                'review_id' => $review->id,
                'product_id' => $review->product_id,
                'operator_id' => $operator?->id,
            ]);
        });

        return $review->delete();
    }

    public function approve(Review $review, User $operator): Review
    {
        return $this->update($review, ['status' => 'approved'], $operator);
    }

    public function reject(Review $review, User $operator): Review
    {
        return $this->update($review, ['status' => 'rejected'], $operator);
    }

    public function toggleVisibility(Review $review, User $operator): Review
    {
        $newStatus = $review->status === 'hidden' ? 'approved' : 'hidden';
        return $this->update($review, ['status' => $newStatus], $operator);
    }

    public function getProductReviews(int $productId, int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->repository->getApprovedByProduct($productId, $perPage);
    }

    public function getProductSummary(int $productId): array
    {
        return $this->repository->getProductSummary($productId);
    }

    public function getAllProductsSummary()
    {
        return $this->repository->getAllProductsSummary();
    }

    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    private function validateReviewData(array $data): void
    {
        if (isset($data['rating'])) {
            if ($data['rating'] < 1 || $data['rating'] > 5) {
                throw new \Exception('评分必须在 1 到 5 星之间');
            }
        }

        if (isset($data['content']) && mb_strlen($data['content']) > 1000) {
            throw new \Exception('评价内容不能超过 1000 个字符');
        }

        if (isset($data['reviewer_name']) && mb_strlen($data['reviewer_name']) > 100) {
            throw new \Exception('评价人名称不能超过 100 个字符');
        }
    }
}
