<?php

namespace App\Http\Resources\Content;

use App\Models\Content\SuggestMessage;
use App\Models\System\Administrator;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property SuggestMessage $resource
 */
class SuggestMessageResource extends JsonResource
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        $sender = $this->resource->sender;

        $senderData = null;
        if ($sender instanceof User) {
            $senderData = [
                'user_id' => $sender->id,
                'nickname' => $sender->profile?->nickname,
                'avatar' => $sender->profile?->avatar_url,
            ];
        } elseif ($sender instanceof Administrator) {
            $senderData = [
                'admin_id' => $sender->id,
                'name' => $sender->name,
            ];
        }

        return [
            'message_id' => $this->resource->id,
            'content' => $this->resource->content,
            'is_from_user' => $this->resource->is_from_user,
            'sender' => $senderData,
            'created_at' => $this->resource->created_at,
        ];
    }
}
