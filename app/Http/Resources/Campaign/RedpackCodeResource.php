<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RedpackCodeResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->resource->code,
            'amount' => (float) $this->resource->amount,
            'status' => $this->resource->status->value,
            'status_label' => $this->resource->status->getLabel(),
            'claimed_at' => $this->resource->claimed_at,
            'redpack' => RedpackResource::make($this->resource->redpack),
            'created_at' => $this->resource->created_at,
        ];
    }
}
