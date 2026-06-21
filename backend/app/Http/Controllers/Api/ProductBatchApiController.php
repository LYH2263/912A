<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Repositories\ProductBatchRepository;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProductBatchApiController extends Controller
{
    public function __construct(
        private ProductBatchRepository $repository,
        private InventoryService $inventoryService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'product_id',
            'sku_id',
            'batch_no',
            'status',
            'is_sellable',
            'has_stock',
            'expiring_soon',
            'expired',
            'expiry_date_start',
            'expiry_date_end',
            'sort_by',
            'sort_order',
        ]);
        $perPage = $request->get('per_page', 15);

        $batches = $this->repository->paginate($filters, $perPage);

        return response()->json([
            'data' => $batches->items(),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
                'last_page' => $batches->lastPage(),
            ],
        ]);
    }

    public function show(ProductBatch $batch): JsonResponse
    {
        $batch->load(['product', 'sku']);
        return response()->json(['data' => $batch]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'sku_id' => 'nullable|exists:product_skus,id',
            'batch_no' => 'required|string|max:100',
            'production_date' => 'required|date',
            'shelf_life_days' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string|max:1000',
        ]);

        $exists = ProductBatch::where('product_id', $validated['product_id'])
            ->where('batch_no', $validated['batch_no'])
            ->exists();
        if ($exists) {
            return response()->json(['message' => '该商品下已存在相同批次号'], 400);
        }

        try {
            $product = Product::findOrFail($validated['product_id']);
            $batch = $this->inventoryService->createBatch($product, $validated, $validated['remark'] ?? '');
            return response()->json(['data' => $batch], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, ProductBatch $batch): JsonResponse
    {
        $validated = $request->validate([
            'batch_no' => 'sometimes|string|max:100',
            'production_date' => 'sometimes|date',
            'shelf_life_days' => 'sometimes|integer|min:1',
            'expiry_date' => 'sometimes|date',
            'unit_cost' => 'sometimes|numeric|min:0',
            'remark' => 'sometimes|nullable|string|max:1000',
            'is_sellable' => 'sometimes|boolean',
        ]);

        if (isset($validated['batch_no'])) {
            $exists = ProductBatch::where('product_id', $batch->product_id)
                ->where('batch_no', $validated['batch_no'])
                ->where('id', '!=', $batch->id)
                ->exists();
            if ($exists) {
                return response()->json(['message' => '该商品下已存在相同批次号'], 400);
            }
        }

        try {
            $updated = $this->repository->update($batch, $validated);
            $product = $batch->product;
            if ($product) {
                $this->inventoryService->recalculateSellableStock($product);
            }
            return response()->json(['data' => $updated->fresh()]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(ProductBatch $batch): JsonResponse
    {
        try {
            $product = $batch->product;
            $this->repository->delete($batch);
            if ($product) {
                $this->inventoryService->recalculateSellableStock($product);
            }
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function adjustQuantity(Request $request, ProductBatch $batch): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'remark' => 'nullable|string|max:1000',
        ]);

        try {
            $updated = $this->inventoryService->adjustBatchQuantity(
                $batch,
                $validated['quantity'],
                $validated['remark'] ?? ''
            );
            return response()->json(['data' => $updated]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function markUnsellable(Request $request, ProductBatch $batch): JsonResponse
    {
        $validated = $request->validate([
            'remark' => 'nullable|string|max:1000',
        ]);

        try {
            $updated = $this->inventoryService->markBatchAsUnsellable(
                $batch,
                $validated['remark'] ?? ''
            );
            return response()->json(['data' => $updated]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function expiringSoon(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 30);
        $perPage = (int) $request->get('per_page', 15);

        $batches = $this->repository->getExpiringSoonBatches($days, $perPage);

        return response()->json([
            'data' => $batches->items(),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
                'last_page' => $batches->lastPage(),
            ],
        ]);
    }

    public function expired(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);

        $batches = $this->repository->getExpiredBatches($perPage);

        return response()->json([
            'data' => $batches->items(),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
                'last_page' => $batches->lastPage(),
            ],
        ]);
    }

    public function summary(): JsonResponse
    {
        $summary = $this->repository->getExpiringSummary();
        return response()->json(['data' => $summary]);
    }

    public function scanStatuses(): JsonResponse
    {
        try {
            $result = $this->repository->scanAndUpdateBatchStatuses();
            return response()->json(['data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function productBatches(int $productId, Request $request): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $perPage = (int) $request->get('per_page', 15);
        $filters = ['product_id' => $productId];

        if ($request->has('has_stock')) {
            $filters['has_stock'] = $request->get('has_stock');
        }
        if ($request->has('status')) {
            $filters['status'] = $request->get('status');
        }

        $batches = $this->repository->paginate($filters, $perPage);

        return response()->json([
            'data' => $batches->items(),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
                'last_page' => $batches->lastPage(),
            ],
        ]);
    }
}
