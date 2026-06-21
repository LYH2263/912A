<?php

namespace App\Services;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductSku;
use App\Repositories\PriceHistoryRepository;
use Illuminate\Support\Facades\Auth;

class PriceHistoryService
{
    public function __construct(
        private PriceHistoryRepository $repository
    ) {
    }

    public function recordPriceChange(
        Product $product,
        float $oldPrice,
        float $newPrice,
        ?string $reason = null,
        ?ProductSku $sku = null
    ): ?PriceHistory {
        if (abs($oldPrice - $newPrice) < 0.0001) {
            return null;
        }

        $operatorId = Auth::check() ? Auth::id() : null;

        return $this->repository->create([
            'product_id' => $product->id,
            'sku_id' => $sku?->id,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'operator_id' => $operatorId,
            'reason' => $reason,
        ]);
    }

    public function getByProductId(int $productId, int $perPage = 15)
    {
        return $this->repository->getByProductId($productId, $perPage);
    }

    public function getAllByProductId(int $productId)
    {
        return $this->repository->getAllByProductId($productId);
    }

    public function getTrendData(int $productId, int $days = 90): array
    {
        $histories = $this->repository->getTrendData($productId, $days);

        $product = Product::find($productId);
        if (!$product) {
            return [
                'dates' => [],
                'prices' => [],
                'has_changes' => false,
                'current_price' => 0,
                'product_name' => '',
            ];
        }

        $dates = [];
        $prices = [];

        if ($histories->count() === 0) {
            return [
                'dates' => [],
                'prices' => [],
                'has_changes' => false,
                'current_price' => (float) $product->price,
                'product_name' => $product->name,
            ];
        }

        $startDate = now()->subDays($days)->startOfDay();

        $firstHistory = $histories->first();
        $dates[] = $startDate->format('Y-m-d H:i');
        $prices[] = (float) $firstHistory->old_price;

        foreach ($histories as $history) {
            $dates[] = $history->created_at->format('Y-m-d H:i');
            $prices[] = (float) $history->new_price;
        }

        $endDate = now();
        $dates[] = $endDate->format('Y-m-d H:i');
        $prices[] = (float) $product->price;

        return [
            'dates' => $dates,
            'prices' => $prices,
            'has_changes' => true,
            'current_price' => (float) $product->price,
            'product_name' => $product->name,
        ];
    }
}
