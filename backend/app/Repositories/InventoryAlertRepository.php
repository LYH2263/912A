<?php

namespace App\Repositories;

use App\Models\LowStockAlert;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class InventoryAlertRepository
{
    /**
     * 获取预警列表（分页）
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = LowStockAlert::with('product');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByRaw("CASE WHEN status = 'unread' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * 获取未读预警数量
     */
    public function getUnreadCount(): int
    {
        return LowStockAlert::where('status', 'unread')->count();
    }

    /**
     * 为单个商品创建或更新未读预警
     * 当库存低于等于阈值（含缺货=0）时触发：
     * - 已有未读预警：更新 current_stock 和 threshold
     * - 无未读预警：创建新预警
     */
    public function createOrUpdateAlert(Product $product): ?LowStockAlert
    {
        if ($product->stock_quantity > $product->low_stock_threshold) {
            return null;
        }

        $existingAlert = LowStockAlert::where('product_id', $product->id)
            ->where('status', 'unread')
            ->first();

        if ($existingAlert) {
            $existingAlert->update([
                'current_stock' => $product->stock_quantity,
                'threshold' => $product->low_stock_threshold,
            ]);
            return $existingAlert;
        }

        $alert = LowStockAlert::create([
            'product_id' => $product->id,
            'current_stock' => $product->stock_quantity,
            'threshold' => $product->low_stock_threshold,
            'status' => 'unread',
        ]);

        Log::info('低库存预警已创建', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'current_stock' => $product->stock_quantity,
            'threshold' => $product->low_stock_threshold,
        ]);

        return $alert;
    }

    /**
     * 扫描并创建/更新低库存预警（含缺货商品）
     */
    public function scanAndCreateAlerts(): array
    {
        $createdCount = 0;
        $updatedCount = 0;

        $lowStockProducts = Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->get();

        foreach ($lowStockProducts as $product) {
            $existingAlert = LowStockAlert::where('product_id', $product->id)
                ->where('status', 'unread')
                ->first();

            if ($existingAlert) {
                if ($existingAlert->current_stock != $product->stock_quantity || $existingAlert->threshold != $product->low_stock_threshold) {
                    $existingAlert->update([
                        'current_stock' => $product->stock_quantity,
                        'threshold' => $product->low_stock_threshold,
                    ]);
                    $updatedCount++;
                }
                continue;
            }

            LowStockAlert::create([
                'product_id' => $product->id,
                'current_stock' => $product->stock_quantity,
                'threshold' => $product->low_stock_threshold,
                'status' => 'unread',
            ]);

            $createdCount++;

            Log::info('低库存预警已创建', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'current_stock' => $product->stock_quantity,
                'threshold' => $product->low_stock_threshold,
            ]);
        }

        return [
            'created' => $createdCount,
            'updated' => $updatedCount,
            'total_low_stock' => $lowStockProducts->count(),
        ];
    }

    /**
     * 标记单条预警为已读
     */
    public function markAsRead(int $alertId, ?int $userId = null): ?LowStockAlert
    {
        $alert = LowStockAlert::find($alertId);
        if (!$alert) {
            return null;
        }
        $alert->markAsRead($userId);
        return $alert;
    }

    /**
     * 标记所有预警为已读
     */
    public function markAllAsRead(?int $userId = null): int
    {
        return LowStockAlert::where('status', 'unread')->update([
            'status' => 'read',
            'read_at' => now(),
            'read_by' => $userId ?? auth()->id(),
        ]);
    }
}
