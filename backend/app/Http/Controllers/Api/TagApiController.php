<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\TagService;
use App\Repositories\TagRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagApiController extends Controller
{
    public function __construct(
        private TagService $service,
        private TagRepository $repository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search']);
        $perPage = $request->get('per_page', 15);

        $tags = $this->repository->paginate($filters, $perPage);

        return response()->json([
            'data' => $tags->items(),
            'meta' => [
                'current_page' => $tags->currentPage(),
                'per_page' => $tags->perPage(),
                'total' => $tags->total(),
                'last_page' => $tags->lastPage(),
            ],
        ]);
    }

    public function all(Request $request): JsonResponse
    {
        $filters = [];
        if ($request->has('ids')) {
            $filters['ids'] = explode(',', $request->input('ids'));
        }
        $tags = $this->repository->all($filters);

        return response()->json(['data' => $tags]);
    }

    public function show(Tag $tag): JsonResponse
    {
        return response()->json(['data' => $tag]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $tag = $this->service->create($validated);
            return response()->json(['data' => $tag], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, Tag $tag): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'color' => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $tag = $this->service->update($tag, $validated);
            return response()->json(['data' => $tag]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(Tag $tag): JsonResponse
    {
        try {
            $this->service->delete($tag);
            return response()->json(['message' => '标签删除成功']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
