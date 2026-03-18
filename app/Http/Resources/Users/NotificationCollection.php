<?php

namespace App\Http\Resources\Users;

use App\Http\Resources\BaseCollection;

class NotificationCollection extends BaseCollection
{
    public function toArray($request): array
    {
        return [
            'list' => $this->collection->map(function ($item) {
                return new NotificationResource($item);
            }),
            'page' => $this->pagination(),
        ];
    }
}
