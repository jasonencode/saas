<?php

namespace App\Http\Resources\Content;

use App\Http\Resources\BaseCollection;
use App\Models\Content\SinglePage;
use Illuminate\Http\Request;

class SinglePageCollection extends BaseCollection
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'list' => $this->collection->map(function (SinglePage $item) {
                return [
                    'single_page_id' => $item->id,
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'cover' => $item->cover_url,
                    'created_at' => $item->created_at,
                ];
            }),
            'page' => $this->pagination(),
        ];
    }
}
