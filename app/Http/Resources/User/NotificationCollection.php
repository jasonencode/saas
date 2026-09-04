<?php

namespace App\Http\Resources\User;

use App\Http\Resources\BaseCollection;

class NotificationCollection extends BaseCollection
{
    public $collects = NotificationResource::class;
}
