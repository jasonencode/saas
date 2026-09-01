<?php

namespace App\Http\Resources\User;

use App\Http\Resources\EnumResource;
use App\Http\Resources\Traits\HasDateTimeFormat;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    use HasDateTimeFormat;

    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'user_id' => $user->id,
            'username' => $user->username,
            // 个人信息
            'profile' => [
                'nickname' => $user->profile?->nickname,
                'avatar' => $user->profile?->avatar_url,
                'gender' => $user->profile?->gender ? EnumResource::make($user->profile->gender) : null,
                'birthday' => $this->formatDate($user->profile?->birthday),
            ],
        ];
    }
}
