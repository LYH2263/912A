<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    /**
     * 获取仪表盘汇总数据
     */
    public function getDashboardSummary(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        $totalInventoryValue = (float) Product::query()
            ->selectRaw('COALESCE(SUM(price * stock_quantity), 0) as total')
            ->value('total');

        return [
            'products' => [
                'total' => Product::count(),
                'active' => Product::where('status', 'active')->count(),
                'inactive' => Product::where('status', 'inactive')->count(),
                'out_of_stock' => Product::where('stock_quantity', 0)->count(),
            ],
            'orders' => [
                'today_count' => Order::whereDate('created_at', $today)->count(),
                'today_amount' => Order::whereDate('created_at', $today)->sum('final_amount'),
                'month_count' => Order::whereDate('created_at', '>=', $thisMonth)->count(),
                'month_amount' => Order::whereDate('created_at', '>=', $thisMonth)->sum('final_amount'),
                'pending' => Order::whereIn('status', ['pending', 'paid'])->count(),
            ],
            'inventory' => [
                'total_value' => $totalInventoryValue,
            ],
        ];
    }

    /**
     * 获取订单趋势数据
     */
    public function getOrderTrends(int $days = 7): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        
        $orders = Order::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(final_amount) as amount')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $orders->map(function ($order) {
            return [
                'date' => $order->date,
                'count' => (int) $order->count,
                'amount' => (float) $order->amount,
            ];
        })->toArray();
    }

    /**
     * 获取商品销售排行
     */
    public function getProductSalesRanking(int $limit = 10): array
    {
        return DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->selectRaw('products.id, products.name, SUM(order_items.quantity) as total_quantity, SUM(order_items.subtotal) as total_amount')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
