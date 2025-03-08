<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'user_id' => $user->id,
            'username' => $user->username,
            // 个人信息
            'profile' => [
                'nickname' => $user->info?->nickname,
                'avatar' => $user->info?->avatar_url,
                'gender' => $user->info?->gender ? [
                    'value' => $user->info->gender->value,
                    'label' => $user->info->gender->getLabel(),
                ] : null,
                'birthday' => $user->info?->birthday?->format('Y-m-d'),
            ],
        ];
    }
}