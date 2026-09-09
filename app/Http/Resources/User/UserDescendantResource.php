<?php

namespace App\Http\Resources\User;

use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDescendantResource extends JsonResource
{
    /**
     * 转换为数组格式
     *
     * 资源为 User 模型，携带 user_relations 表 join 出的 parent_id、layer 字段
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'user_id' => $user->id,
            'username' => $user->username,
            'nickname' => $user->profile?->nickname,
            'avatar' => $user->profile?->avatar_url,
            'created_at' => $user->created_at,
            'parent_id' => $user->parent_id,
            'layer' => (int) $user->layer,
        ];
    }
}
