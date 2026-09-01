<?php

namespace App\Http\Resources\User;

use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property User $resource
 */
class PublicUserResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->resource->id,
            'nickname' => $this->resource->profile?->nickname,
            'avatar' => $this->resource->profile?->avatar_url,
        ];
    }
}
