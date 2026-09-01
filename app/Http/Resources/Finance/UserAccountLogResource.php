<?php

namespace App\Http\Resources\Finance;

use App\Http\Resources\EnumResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 用户账户变动日志资源
 */
class UserAccountLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'log_id' => $this->resource->id,
            'type' => EnumResource::make($this->resource->type),
            'asset' => EnumResource::make($this->resource->asset),
            'amount' => $this->resource->amount,
            'before' => $this->resource->before,
            'after' => $this->resource->after,
            'remark' => $this->resource->remark,
            'created_at' => (string) $this->resource->created_at,
        ];
    }
}
