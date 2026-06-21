<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Customer;
use App\Repositories\CouponRepository;
use Illuminate\Support\Facades\Log;

class CouponService
{
    public function __construct(
        private CouponRepository $repository
    ) {
    }

    public function create(array $data): Coupon
    {
        if ($this->repository->findByCode($data['code'])) {
            throw new \Exception('优惠券代码已存在');
        }

        $this->validateCouponData($data);

        return $this->repository->create($data);
    }

    public function update(Coupon $coupon, array $data): Coupon
    {
        if (isset($data['code']) && $data['code'] !== $coupon->code) {
            $existing = $this->repository->findByCode($data['code']);
            if ($existing && $existing->id !== $coupon->id) {
                throw new \Exception('优惠券代码已存在');
            }
        }

        $this->validateCouponData($data, $coupon);

        return $this->repository->update($coupon, $data);
    }

    public function delete(Coupon $coupon): bool
    {
        if ($coupon->used_quantity > 0) {
            throw new \Exception('已使用的优惠券不能删除，请改为停用');
        }

        return $coupon->delete();
    }

    public function validateAndCalculate(Coupon $coupon, float $orderAmount, ?Customer $customer = null): array
    {
        if (!$coupon->canBeUsedBy($customer)) {
            $reason = $this->getValidationFailureReason($coupon, $customer);
            throw new \Exception($reason);
        }

        if ($orderAmount < $coupon->min_amount) {
            throw new \Exception("订单金额未达到最低消费门槛 ¥{$coupon->min_amount}");
        }

        $discount = $coupon->calculateDiscount($orderAmount);

        return [
            'coupon_id' => $coupon->id,
            'discount_amount' => $discount,
            'final_amount' => round($orderAmount - $discount, 2),
        ];
    }

    public function markAsUsed(Coupon $coupon, ?Customer $customer = null): void
    {
        $coupon->increment('used_quantity');

        if ($customer) {
            $existing = $coupon->customers()->where('customer_id', $customer->id)->first();
            if ($existing) {
                $coupon->customers()->updateExistingPivot($customer->id, [
                    'times_used' => $existing->pivot->times_used + 1,
                ]);
            } else {
                $coupon->customers()->attach($customer->id, ['times_used' => 1]);
            }
        }

        Log::info('优惠券已使用', [
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
            'customer_id' => $customer?->id,
        ]);
    }

    public function getAvailableCoupons(float $orderAmount)
    {
        return $this->repository->getAvailableForAmount($orderAmount);
    }

    private function validateCouponData(array $data, ?Coupon $coupon = null): void
    {
        if (isset($data['type'])) {
            if ($data['type'] === 'percent' && (isset($data['value']) && ($data['value'] <= 0 || $data['value'] > 100))) {
                throw new \Exception('折扣比例必须在 1-100 之间');
            }
            if ($data['type'] === 'fixed' && isset($data['value']) && $data['value'] <= 0) {
                throw new \Exception('固定金额必须大于 0');
            }
        }

        if (isset($data['starts_at']) && isset($data['expires_at'])) {
            if ($data['starts_at'] >= $data['expires_at']) {
                throw new \Exception('开始时间必须早于结束时间');
            }
        }

        if (isset($data['total_quantity']) && $data['total_quantity'] <= 0) {
            throw new \Exception('总发行量必须大于 0');
        }

        if ($coupon && isset($data['total_quantity']) && $data['total_quantity'] < $coupon->used_quantity) {
            throw new \Exception('总发行量不能小于已使用数量');
        }
    }

    private function getValidationFailureReason(Coupon $coupon, ?Customer $customer): string
    {
        if ($coupon->status !== 'active') {
            return '优惠券已停用';
        }
        if ($coupon->isExpired()) {
            return '优惠券已过期';
        }
        if ($coupon->isNotStarted()) {
            return '优惠券尚未开始';
        }
        if ($coupon->isExhausted()) {
            return '优惠券已被领完';
        }
        if ($customer) {
            $pivot = $coupon->customers()->where('customer_id', $customer->id)->first();
            if ($pivot && $pivot->pivot->times_used >= $coupon->per_user_limit) {
                return '已达到该优惠券使用上限';
            }
        }

        return '优惠券不可用';
    }
}
