<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderPreviewItemResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        $orderable = $this->resource->orderable;

        return [
            'orderable' => [
                'id' => $orderable?->getKey(),
                'type' => $orderable?->getMorphClass(),
                'name' => $orderable?->getOrderableName(),
                'spec' => $orderable?->getOrderableSpec(),
                'cover' => $orderable?->getCover(),
            ],
            'qty' => $this->resource->qty,
            'price' => $this->resource->price,
            'sub_total' => $this->resource->sub_total,
        ];
    }
}
