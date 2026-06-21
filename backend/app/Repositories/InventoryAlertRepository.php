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
     * 扫描并创建低库存预警
     */
    public function scanAndCreateAlerts(): array
    {
        $createdCount = 0;
        $skippedCount = 0;

        $lowStockProducts = Product::where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->get();

        foreach ($lowStockProducts as $product) {
            $existingAlert = LowStockAlert::where('product_id', $product->id)
                ->where('status', 'unread')
                ->first();

            if ($existingAlert) {
                $skippedCount++;
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
            'skipped' => $skippedCount,
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
