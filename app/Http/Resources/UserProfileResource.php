<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
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
                'nickname' => $user->profile?->nickname,
                'avatar' => $user->profile?->avatar_url,
                'gender' => $user->profile?->gender ? [
                    'value' => $user->profile->gender->value,
                    'label' => $user->profile->gender->getLabel(),
                ] : null,
                'birthday' => $user->profile?->birthday?->format('Y-m-d'),
            ],
        ];
    }
}
