<?php

namespace App\Models\User;

use App\Policies\User\NotificationPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Notifications\DatabaseNotification;

#[Unguarded]
#[UsePolicy(NotificationPolicy::class)]
class Notification extends DatabaseNotification
{

}
