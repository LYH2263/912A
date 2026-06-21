<?php

namespace App\Http\Resources;

use App\Http\Resources\CouponResource;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'order_no' => $this->order_no,
            'user_id' => $this->user_id,
            'coupon_id' => $this->coupon_id,
            'total_amount' => (float) $this->total_amount,
            'discount_amount' => (float) $this->discount_amount,
            'final_amount' => (float) $this->final_amount,
            'status' => $this->status,
            'shipping_address' => $this->shipping_address,
            'shipping_name' => $this->shipping_name,
            'shipping_phone' => $this->shipping_phone,
            'remark' => $this->remark,
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'shipped_at' => $this->shipped_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'cancelled_at' => $this->cancelled_at?->toDateTimeString(),
            'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'coupon' => new CouponResource($this->whenLoaded('coupon')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
