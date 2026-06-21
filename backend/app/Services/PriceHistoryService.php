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
            return ['dates' => [], 'prices' => []];
        }

        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        $pricePoints = [];

        $firstHistory = $histories->first();
        if ($firstHistory) {
            $pricePoints[$startDate->format('Y-m-d')] = (float) $firstHistory->old_price;
        } else {
            $pricePoints[$startDate->format('Y-m-d')] = (float) $product->price;
        }

        foreach ($histories as $history) {
            $date = $history->created_at->format('Y-m-d');
            $pricePoints[$date] = (float) $history->new_price;
        }

        $pricePoints[$endDate->format('Y-m-d')] = (float) $product->price;

        ksort($pricePoints);

        $dates = array_keys($pricePoints);
        $prices = array_values($pricePoints);

        return [
            'dates' => $dates,
            'prices' => $prices,
            'current_price' => (float) $product->price,
            'product_name' => $product->name,
        ];
    }
}
