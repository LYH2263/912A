<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponApiController extends Controller
{
    public function __construct(
        private CouponService $service,
        private \App\Repositories\CouponRepository $repository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'type', 'search']);
        $perPage = $request->get('per_page', 15);

        $coupons = $this->repository->paginate($filters, $perPage);

        return response()->json([
            'data' => CouponResource::collection($coupons->items()),
            'meta' => [
                'current_page' => $coupons->currentPage(),
                'per_page' => $coupons->perPage(),
                'total' => $coupons->total(),
                'last_page' => $coupons->lastPage(),
            ],
        ]);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        return response()->json(['data' => new CouponResource($coupon)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'code' => 'required|string|max:50|unique:coupons,code',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0.01',
            'min_amount' => 'nullable|numeric|min:0',
            'total_quantity' => 'required|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'status' => 'nullable|in:active,inactive',
        ]);

        try {
            $coupon = $this->service->create($validated);
            return response()->json(['data' => new CouponResource($coupon)], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'code' => 'sometimes|string|max:50|unique:coupons,code,' . $coupon->id,
            'type' => 'sometimes|in:fixed,percent',
            'value' => 'sometimes|numeric|min:0.01',
            'min_amount' => 'nullable|numeric|min:0',
            'total_quantity' => 'sometimes|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'status' => 'nullable|in:active,inactive',
        ]);

        try {
            $coupon = $this->service->update($coupon, $validated);
            return response()->json(['data' => new CouponResource($coupon)]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        try {
            $this->service->delete($coupon);
            return response()->json(['message' => '优惠券删除成功']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coupon_id' => 'required|exists:coupons,id',
            'order_amount' => 'required|numeric|min:0',
        ]);

        try {
            $coupon = $this->repository->find($validated['coupon_id']);
            $user = $request->user();
            $result = $this->service->validateAndCalculate($coupon, (float) $validated['order_amount'], $user);
            return response()->json(['data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function available(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_amount' => 'required|numeric|min:0',
        ]);

        $coupons = $this->service->getAvailableCoupons((float) $validated['order_amount']);
        return response()->json(['data' => CouponResource::collection($coupons)]);
    }
}
