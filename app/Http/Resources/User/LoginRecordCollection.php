<?php

namespace App\Http\Resources\User;

use App\Http\Resources\BaseCollection;

class LoginRecordCollection extends BaseCollection
{
    public $collects = LoginRecordResource::class;
}
