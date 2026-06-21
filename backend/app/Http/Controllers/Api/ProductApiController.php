<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    public function __construct(
        private ProductService $service,
        private \App\Repositories\ProductRepository $repository
    ) {
    }

    /**
     * 获取商品列表
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id', 'status', 'search']);
        $perPage = $request->get('per_page', 15);

        $products = $this->repository->paginate($filters, $perPage);

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * 获取商品详情
     */
    public function show(Product $product): JsonResponse
    {
        $product->load('category', 'inventoryLogs');
        return response()->json(['data' => new ProductResource($product)]);
    }

    /**
     * 创建商品
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'sku' => 'required|string|max:100|unique:products,sku',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'images' => 'nullable|array',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
        ]);

        try {
            $product = $this->service->create($validated);
            return response()->json(['data' => new ProductResource($product)], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 更新商品
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'sku' => 'sometimes|string|max:100|unique:products,sku,' . $product->id,
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'images' => 'nullable|array',
            'status' => 'sometimes|in:active,inactive,sold_out',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
        ]);

        try {
            $product = $this->service->update($product, $validated);
            return response()->json(['data' => new ProductResource($product)]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 删除商品
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            $this->service->delete($product);
            return response()->json(['message' => '商品删除成功']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
