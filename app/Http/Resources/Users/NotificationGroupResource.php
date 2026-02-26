<?php

namespace App\Http\Resources\Users;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $model = DatabaseNotification::whereMorphedTo('notifiable', Auth::user())
            ->where('type', $this->resource->type);
        $newest = $model->whereNull('read_at')->first();

        return [
            'title' => resolve($this->resource->type)->getGroupTitle(),
            'group' => class_basename($this->resource->type),
            'total' => $model->count(),
            'unread' => $model->whereNull('read_at')->count(),
            'newest' => $this->when(!is_null($newest), new NotificationResource($newest), null),
        ];
    }
}
