<?php

namespace App\Http\Resources\Contents;

use App\Http\Resources\BaseCollection;
use App\Models\Content\Content;
use Illuminate\Http\Request;

class ContentCollection extends BaseCollection
{
    public function toArray(Request $request): array
    {
        return [
            'list' => $this->collection->map(function (Content $item) {
                return [
                    'content_id' => $item->id,
                    'title' => $item->title,
                    'cover' => $item->cover_url,
                    'created_at' => $item->created_at,
                ];
            }),
            'page' => $this->pagination(),
        ];
    }
}
