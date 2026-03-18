<?php

namespace App\Http\Resources\Users;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'notification_id' => $this->resource->id,
            'title' => $this->resource->data['title'],
            'type' => class_basename($this->resource->type),
            'data' => $this->parseData(),
            'read' => $this->resource->read(),
            'read_at' => (string) $this->resource->read_at,
            'created_at' => (string) $this->resource->created_at,
        ];
    }

    protected function parseData(): array
    {
        $data = $this->resource->data;

        return [
            'title' => $data['title'],
            'body' => $data['body'],
            'color' => $data['color'],
            'icon' => $data['icon'],
            'iconColor' => $data['iconColor'],
            'status' => $data['status'],
        ];
    }
}
