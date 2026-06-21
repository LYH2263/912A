<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Repositories\InventoryRepository;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryApiController extends Controller
{
    public function __construct(
        private InventoryRepository $repository,
        private InventoryService $service
    ) {
    }

    /**
     * 获取库存列表
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id', 'low_stock', 'out_of_stock', 'sufficient']);
        $perPage = $request->get('per_page', 15);

        $inventory = $this->repository->paginate($filters, $perPage);

        return response()->json([
            'data' => $inventory->items(),
            'meta' => [
                'current_page' => $inventory->currentPage(),
                'per_page' => $inventory->perPage(),
                'total' => $inventory->total(),
                'last_page' => $inventory->lastPage(),
            ],
        ]);
    }

    /**
     * 获取商品库存详情
     */
    public function show(Product $product): JsonResponse
    {
        $product->load('category', 'inventoryLogs');
        return response()->json(['data' => $product]);
    }

    /**
     * 更新库存数量
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'remark' => 'nullable|string|max:500',
        ]);

        try {
            $log = $this->service->adjustStock($product, $validated['quantity'], $validated['remark'] ?? '');
            return response()->json(['data' => $log]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

}
