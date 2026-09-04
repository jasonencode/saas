<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\BaseCollection;

class OrderCollection extends BaseCollection
{
    public $collects = OrderResource::class;
}
