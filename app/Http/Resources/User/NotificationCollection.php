<?php

namespace App\Http\Resources\User;

use App\Http\Resources\BaseCollection;
use Illuminate\Http\Request;

class NotificationCollection extends BaseCollection
{
    /**
     * 转换为数组格式
     */
    public function toArray(Request $request): array
    {
        return [
            'list' => $this->collection->map(function ($item) {
                return new NotificationResource($item);
            }),
            'page' => $this->pagination(),
        ];
    }
}
