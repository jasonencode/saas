<?php

namespace App\Http\Resources\Content;

use App\Models\Content\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Comment $resource
 */
class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource->user;

        return [
            'comment_id' => $this->resource->id,
            'user' => [
                'user_id' => $user?->id,
                'nickname' => $user?->profile?->nickname,
                'avatar' => $user?->profile?->avatar_url,
            ],
            'content' => $this->resource->content,
            'star' => $this->resource->star,
            'pictures' => $this->resource->picture_urls,
            'created_at' => $this->resource->created_at,
        ];
    }
}
