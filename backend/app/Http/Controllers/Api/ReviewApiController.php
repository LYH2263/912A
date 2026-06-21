<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewApiController extends Controller
{
    public function __construct(
        private ReviewService $service,
        private \App\Repositories\ReviewRepository $repository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['product_id', 'status', 'rating', 'min_rating', 'search']);
        $perPage = $request->get('per_page', 15);

        $reviews = $this->repository->paginate($filters, $perPage);

        return response()->json([
            'data' => ReviewResource::collection($reviews->items()),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'last_page' => $reviews->lastPage(),
            ],
        ]);
    }

    public function show(Review $review): JsonResponse
    {
        return response()->json(['data' => new ReviewResource($this->repository->find($review->id))]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'user_id' => 'nullable|exists:users,id',
            'reviewer_name' => 'nullable|string|max:100',
            'rating' => 'required|integer|between:1,5',
            'content' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:pending,approved,rejected,hidden',
        ]);

        try {
            $review = $this->service->create($validated, Auth::user());
            return response()->json(['data' => new ReviewResource($review)], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, Review $review): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'user_id' => 'nullable|exists:users,id',
            'reviewer_name' => 'nullable|string|max:100',
            'rating' => 'sometimes|integer|between:1,5',
            'content' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:pending,approved,rejected,hidden',
        ]);

        try {
            $review = $this->service->update($review, $validated, Auth::user());
            return response()->json(['data' => new ReviewResource($review)]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(Review $review): JsonResponse
    {
        try {
            $this->service->delete($review, Auth::user());
            return response()->json(['message' => '评价删除成功']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function approve(Review $review): JsonResponse
    {
        try {
            $review = $this->service->approve($review, Auth::user());
            return response()->json(['data' => new ReviewResource($review)]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function reject(Review $review): JsonResponse
    {
        try {
            $review = $this->service->reject($review, Auth::user());
            return response()->json(['data' => new ReviewResource($review)]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function toggleVisibility(Review $review): JsonResponse
    {
        try {
            $review = $this->service->toggleVisibility($review, Auth::user());
            return response()->json(['data' => new ReviewResource($review)]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function productReviews(int $productId, Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $reviews = $this->service->getProductReviews($productId, $perPage);

        return response()->json([
            'data' => ReviewResource::collection($reviews->items()),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'last_page' => $reviews->lastPage(),
            ],
        ]);
    }

    public function productSummary(int $productId): JsonResponse
    {
        $summary = $this->service->getProductSummary($productId);
        return response()->json(['data' => $summary]);
    }

    public function productsSummary(Request $request): JsonResponse
    {
        $productIds = $request->get('product_ids', []);
        $summaries = $this->service->getAllProductsSummary();

        if (!empty($productIds)) {
            $productIds = is_array($productIds) ? $productIds : explode(',', $productIds);
            $summaries = $summaries->only($productIds);
        }

        return response()->json(['data' => $summaries->values()]);
    }

    public function statistics(): JsonResponse
    {
        $statistics = $this->service->getStatistics();
        return response()->json(['data' => $statistics]);
    }
}
