<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerApiController extends Controller
{
    public function __construct(
        private CustomerService $service,
        private \App\Repositories\CustomerRepository $repository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'min_order_count', 'min_total_spent']);
        $perPage = $request->get('per_page', 15);

        $customers = $this->repository->paginate($filters, $perPage);

        return response()->json([
            'data' => CustomerResource::collection($customers->items()),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'last_page' => $customers->lastPage(),
            ],
        ]);
    }

    public function all(Request $request): JsonResponse
    {
        $filters = $request->only(['search']);
        $customers = $this->repository->all($filters);

        return response()->json([
            'data' => CustomerResource::collection($customers),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => 'required|string|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $customers = $this->repository->search(
            $validated['keyword'],
            $validated['limit'] ?? 20
        );

        return response()->json([
            'data' => CustomerResource::collection($customers),
        ]);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customerWithOrders = $this->repository->findWithOrders($customer->id);
        return response()->json(['data' => new CustomerResource($customerWithOrders)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:customers,phone',
            'address' => 'nullable|string|max:500',
        ]);

        try {
            $validated['order_count'] = 0;
            $validated['total_spent'] = 0;
            $customer = $this->service->create($validated);
            return response()->json(['data' => new CustomerResource($customer)], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'phone' => 'sometimes|string|max:20|unique:customers,phone,' . $customer->id,
            'address' => 'nullable|string|max:500',
        ]);

        try {
            $customer = $this->service->update($customer, $validated);
            return response()->json(['data' => new CustomerResource($customer)]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(Customer $customer): JsonResponse
    {
        try {
            $this->service->delete($customer);
            return response()->json(['message' => '客户删除成功']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
