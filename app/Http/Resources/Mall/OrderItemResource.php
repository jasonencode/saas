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
        return [
            'item_id' => $this->resource->id,
            'orderable' => $this->resource->orderable ? [
                'id' => $this->resource->orderable->getKey(),
                'type' => $this->resource->orderable->getMorphClass(),
                'name' => $this->resource->orderable->getOrderableName(),
                'cover' => $this->resource->orderable->getCover(),
            ] : null,
            'qty' => $this->resource->qty,
            'price' => $this->resource->price,
            'sub_total' => $this->resource->sub_total,
            'remark' => $this->resource->remark ?? '',
        ];
    }
}
