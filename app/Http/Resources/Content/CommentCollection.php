<?php

namespace App\Http\Resources\Content;

use App\Http\Resources\BaseCollection;
use App\Models\Content\Comment;
use Illuminate\Http\Request;

class CommentCollection extends BaseCollection
{
    public function toArray(Request $request): array
    {
        return [
            'list' => $this->collection->map(function (Comment $item) {
                $user = $item->user;

                return [
                    'comment_id' => $item->id,
                    'user' => [
                        'user_id' => $user?->id,
                        'nickname' => $user?->profile?->nickname,
                        'avatar' => $user?->profile?->avatar_url,
                    ],
                    'content' => $item->content,
                    'star' => $item->star,
                    'pictures' => $item->picture_urls,
                    'created_at' => $item->created_at,
                ];
            }),
            'page' => $this->pagination(),
        ];
    }
}
