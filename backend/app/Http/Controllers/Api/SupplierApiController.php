<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierApiController extends Controller
{
    public function __construct(
        private SupplierService $service,
        private \App\Repositories\SupplierRepository $repository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'search']);
        $perPage = $request->get('per_page', 15);

        $suppliers = $this->repository->paginate($filters, $perPage);

        return response()->json([
            'data' => SupplierResource::collection($suppliers->items()),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
                'last_page' => $suppliers->lastPage(),
            ],
        ]);
    }

    public function all(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        $suppliers = $this->repository->all($filters);

        return response()->json([
            'data' => SupplierResource::collection($suppliers),
        ]);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $supplierWithProducts = $this->repository->findWithProducts($supplier->id);
        return response()->json(['data' => new SupplierResource($supplierWithProducts)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'contact' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'status' => 'sometimes|in:active,inactive',
        ]);

        try {
            $supplier = $this->service->create($validated);
            return response()->json(['data' => new SupplierResource($supplier)], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'contact' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'status' => 'sometimes|in:active,inactive',
        ]);

        try {
            $supplier = $this->service->update($supplier, $validated);
            return response()->json(['data' => new SupplierResource($supplier)]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        try {
            $this->service->delete($supplier);
            return response()->json(['message' => '供应商删除成功']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function toggleStatus(Supplier $supplier): JsonResponse
    {
        try {
            $supplier = $this->service->toggleStatus($supplier);
            return response()->json(['data' => new SupplierResource($supplier)]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function getProductCount(Supplier $supplier): JsonResponse
    {
        $count = $this->service->getProductCount($supplier->id);
        return response()->json([
            'data' => [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'product_count' => $count,
            ],
        ]);
    }
}
