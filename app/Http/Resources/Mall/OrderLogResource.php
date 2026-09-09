<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\EnumResource;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderLogResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'log_id' => $this->resource->id,
            'action' => EnumResource::make($this->resource->action),
            'operator' => $this->resource->operator ? [
                'id' => $this->resource->operator_id,
                'type' => $this->resource->operator_type,
                'name' => $this->resource->operator instanceof User
                    ? $this->resource->operator->username
                    : ($this->resource->operator->name ?? '系统'),
            ] : null,
            'context' => $this->resource->context,
            'created_at' => $this->resource->created_at,
        ];
    }
}
