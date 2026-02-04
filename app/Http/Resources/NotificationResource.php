<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'notification_id' => $this->id,
            'title' => $this->data['title'],
            'type' => class_basename($this->type),
            'data' => $this->parseData(),
            'read' => $this->read(),
            'read_at' => (string) $this->read_at,
            'created_at' => (string) $this->created_at,
        ];
    }

    protected function parseData(): array
    {
        $data = $this->data;

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
