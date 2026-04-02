<?php

namespace App\Http\Controllers;

use App\Models\Model;
use Illuminate\Auth\Access\AuthorizationException;
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
