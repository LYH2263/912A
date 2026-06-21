<?php

namespace App\Services;

use App\Models\LowStockAlert;
use App\Models\Product;
use App\Repositories\InventoryAlertRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryAlertService
{
    public function __construct(
        private InventoryAlertRepository $repository
    ) {
    }

    /**
     * 获取预警列表
     */
    public function getAlerts(array $filters = [], int $perPage = 15)
    {
        return $this->repository->paginate($filters, $perPage);
    }

    /**
     * 获取未读预警数量
     */
    public function getUnreadCount(): int
    {
        return $this->repository->getUnreadCount();
    }

    /**
     * 扫描库存并创建预警
     */
    public function scanLowStock(): array
    {
        return DB::transaction(function () {
            $result = $this->repository->scanAndCreateAlerts();

            Log::info('低库存预警扫描完成', $result);

            return $result;
        });
    }

    /**
     * 标记单条预警为已读
     */
    public function markAsRead(int $alertId): ?LowStockAlert
    {
        return DB::transaction(function () use ($alertId) {
            return $this->repository->markAsRead($alertId);
        });
    }

    /**
     * 标记所有预警为已读
     */
    public function markAllAsRead(): int
    {
        return DB::transaction(function () {
            return $this->repository->markAllAsRead();
        });
    }

    /**
     * 创建单条预警（手动）
     */
    public function createAlert(int $productId, int $currentStock, int $threshold): LowStockAlert
    {
        return DB::transaction(function () use ($productId, $currentStock, $threshold) {
            $alert = LowStockAlert::create([
                'product_id' => $productId,
                'current_stock' => $currentStock,
                'threshold' => $threshold,
                'status' => 'unread',
            ]);

            Log::info('低库存预警已创建', [
                'alert_id' => $alert->id,
                'product_id' => $productId,
                'current_stock' => $currentStock,
                'threshold' => $threshold,
            ]);

            return $alert;
        });
    }

    /**
     * 为单个商品创建或更新未读预警（库存变动时调用）
     */
    public function createOrUpdateAlert(Product $product): ?LowStockAlert
    {
        return DB::transaction(function () use ($product) {
            return $this->repository->createOrUpdateAlert($product);
        });
    }
}
