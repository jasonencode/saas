<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundLogResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'action' => [
                'value' => $this->resource->action->value,
                'label' => $this->resource->action->getLabel(),
            ],
            'remark' => $this->resource->remark,
            'context' => $this->resource->context,
            'created_at' => $this->resource->created_at?->toDateTimeString(),
        ];
    }
}
