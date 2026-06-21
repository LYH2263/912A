<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReturnResource;
use App\Models\ReturnRequest;
use App\Services\ReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnApiController extends Controller
{
    public function __construct(
        private ReturnService $service,
        private \App\Repositories\ReturnRepository $repository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'type', 'return_no', 'order_no', 'start_date', 'end_date']);
        $perPage = $request->get('per_page', 15);

        $returns = $this->repository->paginate($filters, $perPage);

        return response()->json([
            'data' => ReturnResource::collection($returns->items()),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
                'last_page' => $returns->lastPage(),
            ],
        ]);
    }

    public function show(ReturnRequest $return): JsonResponse
    {
        $return->load(['order', 'orderItem.product', 'orderItem.sku', 'operator']);
        return response()->json(['data' => new ReturnResource($return)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_item_id' => 'required|exists:order_items,id',
            'type' => 'required|in:return,exchange',
            'reason' => 'required|string',
            'refund_amount' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $return = $this->service->create($validated);
            return response()->json(['data' => new ReturnResource($return)], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function approve(ReturnRequest $return): JsonResponse
    {
        try {
            $return = $this->service->approve($return);
            return response()->json(['data' => new ReturnResource($return)]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function reject(Request $request, ReturnRequest $return): JsonResponse
    {
        $validated = $request->validate([
            'reject_reason' => 'required|string',
        ]);

        try {
            $return = $this->service->reject($return, $validated['reject_reason']);
            return response()->json(['data' => new ReturnResource($return)]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function complete(ReturnRequest $return): JsonResponse
    {
        try {
            $return = $this->service->complete($return);
            return response()->json(['data' => new ReturnResource($return)]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
