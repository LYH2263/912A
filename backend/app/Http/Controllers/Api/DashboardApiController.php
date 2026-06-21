<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function __construct(
        private StatisticsService $service
    ) {
    }

    /**
     * 获取仪表盘汇总数据
     */
    public function summary(): JsonResponse
    {
        $data = $this->service->getDashboardSummary();
        return response()->json(['data' => $data]);
    }

    /**
     * 获取图表数据
     */
    public function charts(Request $request): JsonResponse
    {
        $days = $request->get('days', 7);
        
        $orderTrends = $this->service->getOrderTrends($days);
        $productSales = $this->service->getProductSalesRanking(10);

        return response()->json([
            'data' => [
                'order_trends' => $orderTrends,
                'product_sales' => $productSales,
            ],
        ]);
    }
}
