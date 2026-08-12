<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundExpressResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'express_id' => $this->resource->express_id,
            'express_name' => $this->resource->express?->name,
            'express_no' => $this->resource->express_no,
            'status' => [
                'value' => $this->resource->status->value,
                'label' => $this->resource->status->getLabel(),
            ],
            'shipped_at' => $this->resource->shipped_at?->toDateTimeString(),
            'received_at' => $this->resource->received_at?->toDateTimeString(),
        ];
    }
}
