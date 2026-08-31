<?php

namespace App\Http\Resources\Mall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderCreatedResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'tenant_id' => $this->resource->tenant_id,
            'no' => $this->resource->no,
            'total_amount' => $this->resource->total_amount,
        ];
    }
}
