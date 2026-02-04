<?php

namespace App\Contracts\Notification;

use Filament\Notifications\Notification;

class DatabaseMessage extends Notification
{
    public function __get(string $name)
    {
        if ($name === 'data') {
            $data = $this->toArray();
            $data['duration'] = 'persistent';
            $data['format'] = 'laravel';
            unset($data['id']);

            return $data;
        }

        return null;
    }
}
