<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Notifications\DatabaseNotification;

#[Unguarded]
class Notification extends DatabaseNotification
{

}
