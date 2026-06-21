<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InventoryAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryAlertApiController extends Controller
{
    public function __construct(
        private InventoryAlertService $service
    ) {
    }

    /**
     * 获取预警列表
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        $perPage = $request->get('per_page', 15);

        $alerts = $this->service->getAlerts($filters, $perPage);

        return response()->json([
            'data' => $alerts->items(),
            'meta' => [
                'current_page' => $alerts->currentPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
                'last_page' => $alerts->lastPage(),
            ],
        ]);
    }

    /**
     * 获取未读预警数量
     */
    public function unreadCount(): JsonResponse
    {
        $count = $this->service->getUnreadCount();
        return response()->json(['data' => ['count' => $count]]);
    }

    /**
     * 标记单条预警为已读
     */
    public function markAsRead(int $id): JsonResponse
    {
        $alert = $this->service->markAsRead($id);
        if (!$alert) {
            return response()->json(['message' => '预警记录不存在'], 404);
        }
        return response()->json(['data' => $alert]);
    }

    /**
     * 标记所有预警为已读
     */
    public function markAllAsRead(): JsonResponse
    {
        $count = $this->service->markAllAsRead();
        return response()->json(['data' => ['marked_count' => $count]]);
    }

    /**
     * 手动触发扫描（用于测试或手动触发）
     */
    public function scan(): JsonResponse
    {
        $result = $this->service->scanLowStock();
        return response()->json(['data' => $result]);
    }
}
