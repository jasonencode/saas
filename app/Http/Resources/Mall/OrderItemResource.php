<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        $orderable = $this->whenLoaded('orderable');

        return [
            'item_id' => $this->resource->id,
            'orderable' => $orderable ? [
                'id' => $orderable->getKey(),
                'type' => $orderable->getMorphClass(),
                'name' => $orderable->getOrderableName(),
                'cover' => $orderable->getCover(),
            ] : null,
            'qty' => $this->resource->qty,
            'price' => $this->resource->price,
            'sub_total' => $this->resource->sub_total,
            'remark' => $this->resource->remark ?? '',
        ];
    }
}
