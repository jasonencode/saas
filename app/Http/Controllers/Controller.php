<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    protected function checkPermission(Model $model): void
    {
        if ($model->user && $model->user->isNot(Auth::user())) {
            throw new AuthorizationException;
        }
    }
}
