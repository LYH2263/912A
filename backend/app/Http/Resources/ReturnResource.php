<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'return_no' => $this->return_no,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'type' => $this->type,
            'type_text' => $this->type === 'return' ? '退货' : '换货',
            'reason' => $this->reason,
            'refund_amount' => (float) $this->refund_amount,
            'quantity' => (int) $this->quantity,
            'status' => $this->status,
            'status_text' => $this->getStatusText($this->status),
            'reject_reason' => $this->reject_reason,
            'approved_at' => $this->approved_at?->toDateTimeString(),
            'rejected_at' => $this->rejected_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'operator_id' => $this->operator_id,
            'order' => new OrderResource($this->whenLoaded('order')),
            'order_item' => new OrderItemResource($this->whenLoaded('orderItem')),
            'operator' => $this->whenLoaded('operator', function () {
                return [
                    'id' => $this->operator->id,
                    'name' => $this->operator->name,
                    'email' => $this->operator->email,
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    private function getStatusText(string $status): string
    {
        $map = [
            'pending' => '待审核',
            'approved' => '已通过',
            'rejected' => '已拒绝',
            'completed' => '已完成',
        ];
        return $map[$status] ?? $status;
    }
}
