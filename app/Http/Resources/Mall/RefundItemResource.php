<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundItemResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        $orderItem = $this->resource->orderItem;

        return [
            'item_id' => $this->resource->id,
            'order_item_id' => $this->resource->order_item_id,
            'orderable' => [
                'orderable_id' => $orderItem?->orderable_id,
                'name' => $orderItem?->orderable_name,
            ],
            'qty' => $this->resource->qty,
            'price' => $this->resource->price,
            'remark' => $this->resource->remark ?? '',
        ];
    }
}
