<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'notification_id' => $this->resource->id,
            'title' => $this->when(
                method_exists($this->resource->type, 'getTitle'),
                $this->resource->type::getTitle(),
                $this->resource->type
            ),
            'type' => $this->resource->type,
            'data' => $this->resource->data,
            'read' => $this->resource->read(),
            'read_at' => (string) $this->resource->read_at,
            'created_at' => (string) $this->resource->created_at,
        ];
    }
}
