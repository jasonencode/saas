<?php

namespace App\Http\Resources\Contents;

use App\Http\Resources\BaseCollection;
use Illuminate\Http\Request;

class ContentCollection extends BaseCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($item) {
                return [
                    'content_id' => $item->id,
                    'title' => $item->title,
                    'created_at' => $item->created_at,
                ];
            }),
            'page' => $this->pagination(),
        ];
    }
}