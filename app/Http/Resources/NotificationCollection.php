<?php

namespace App\Http\Resources;

class NotificationCollection extends BaseCollection
{
    public function toArray($request): array
    {
        return [
            'list' => $this->collection->map(function($item) {
                return new NotificationResource($item);
            }),
            'page' => $this->pagination(),
        ];
    }
}
