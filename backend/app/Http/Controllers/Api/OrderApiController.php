<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderApiController extends Controller
{
    public function __construct(
        private OrderService $service,
        private \App\Repositories\OrderRepository $repository
    ) {
    }

    /**
     * 获取订单列表
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'order_no', 'start_date', 'end_date']);
        $perPage = $request->get('per_page', 15);

        $orders = $this->repository->paginate($filters, $perPage);

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * 获取订单详情
     */
    public function show(Order $order): JsonResponse
    {
        $order->load('orderItems.product');
        return response()->json(['data' => new OrderResource($order)]);
    }

    /**
     * 创建订单
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'discount_amount' => 'nullable|numeric|min:0',
            'shipping_address' => 'nullable|string',
            'shipping_name' => 'nullable|string|max:100',
            'shipping_phone' => 'nullable|string|max:20',
            'remark' => 'nullable|string',
        ]);

        try {
            $order = $this->service->create($validated);
            return response()->json(['data' => new OrderResource($order)], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 更新订单状态
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,paid,shipped,completed,cancelled',
        ]);

        try {
            $order = $this->service->updateStatus($order, $validated['status']);
            return response()->json(['data' => new OrderResource($order)]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
