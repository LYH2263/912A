<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PriceHistoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\PriceHistoryService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    public function __construct(
        private ProductService $service,
        private \App\Repositories\ProductRepository $repository,
        private PriceHistoryService $priceHistoryService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id', 'supplier_id', 'status', 'stock_status', 'search']);
        if ($request->has('tag_ids')) {
            $tagIds = $request->input('tag_ids');
            $filters['tag_ids'] = is_array($tagIds) ? $tagIds : explode(',', $tagIds);
        }
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

    public function show(Product $product): JsonResponse
    {
        $productWithSpecs = $this->service->getProductWithSpecs($product->id);
        return response()->json(['data' => new ProductResource($productWithSpecs)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'sku' => 'required|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'images' => 'nullable|array',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'specs' => 'nullable|array',
            'specs.*.name' => 'required|string|max:50',
            'specs.*.values' => 'required|array|min:1',
            'specs.*.values.*' => 'required|string|max:100',
            'skus' => 'nullable|array',
            'skus.*.sku' => 'required|string|max:100',
            'skus.*.price' => 'required|numeric|min:0',
            'skus.*.cost_price' => 'nullable|numeric|min:0',
            'skus.*.stock_quantity' => 'nullable|integer|min:0',
            'skus.*.image' => 'nullable|string|max:255',
            'skus.*.spec_data' => 'nullable|array',
            'skus.*.status' => 'nullable|in:active,inactive',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        try {
            $product = $this->service->create($validated);
            return response()->json(['data' => new ProductResource($product)], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'sku' => 'sometimes|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'images' => 'nullable|array',
            'status' => 'sometimes|in:active,inactive,sold_out',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'specs' => 'nullable|array',
            'specs.*.name' => 'required|string|max:50',
            'specs.*.values' => 'required|array|min:1',
            'specs.*.values.*' => 'required|string|max:100',
            'skus' => 'nullable|array',
            'skus.*.id' => 'nullable|integer',
            'skus.*.sku' => 'required|string|max:100',
            'skus.*.price' => 'required|numeric|min:0',
            'skus.*.cost_price' => 'nullable|numeric|min:0',
            'skus.*.stock_quantity' => 'nullable|integer|min:0',
            'skus.*.image' => 'nullable|string|max:255',
            'skus.*.spec_data' => 'nullable|array',
            'skus.*.status' => 'nullable|in:active,inactive',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'price_reason' => 'nullable|string|max:500',
        ]);

        try {
            $product = $this->service->update($product, $validated);
            return response()->json(['data' => new ProductResource($product)]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function priceHistories(Request $request, Product $product): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $histories = $this->priceHistoryService->getByProductId($product->id, $perPage);

        return response()->json([
            'data' => PriceHistoryResource::collection($histories->items()),
            'meta' => [
                'current_page' => $histories->currentPage(),
                'per_page' => $histories->perPage(),
                'total' => $histories->total(),
                'last_page' => $histories->lastPage(),
            ],
        ]);
    }

    public function priceTrend(Request $request, Product $product): JsonResponse
    {
        $days = $request->get('days', 90);
        $trendData = $this->priceHistoryService->getTrendData($product->id, (int) $days);

        return response()->json(['data' => $trendData]);
    }

    public function destroy(Product $product): JsonResponse
    {
        try {
            $this->service->delete($product);
            return response()->json(['message' => '商品删除成功']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function batchAttachTags(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
            'tag_ids' => 'required|array|min:1',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        try {
            $count = $this->repository->attachTags($validated['product_ids'], $validated['tag_ids']);
            return response()->json([
                'message' => '批量打标成功',
                'affected_count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function batchDetachTags(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
            'tag_ids' => 'required|array|min:1',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        try {
            $count = $this->repository->detachTags($validated['product_ids'], $validated['tag_ids']);
            return response()->json([
                'message' => '批量去标成功',
                'affected_count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
