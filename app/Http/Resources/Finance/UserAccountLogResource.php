<?php

namespace App\Http\Resources\Finance;

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
            'type' => [
                'value' => $this->resource->type->value,
                'label' => $this->resource->type->getLabel(),
            ],
            'asset' => [
                'value' => $this->resource->asset->value,
                'label' => $this->resource->asset->getLabel(),
            ],
            'amount' => $this->resource->amount,
            'before' => $this->resource->before,
            'after' => $this->resource->after,
            'remark' => $this->resource->remark,
            'created_at' => (string) $this->resource->created_at,
        ];
    }
}
