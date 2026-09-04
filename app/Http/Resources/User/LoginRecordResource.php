<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginRecordResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'ip' => $this->resource->ip,
            'user_agent' => $this->resource->user_agent,
            'created_at' => (string) $this->resource->created_at,
        ];
    }
}
