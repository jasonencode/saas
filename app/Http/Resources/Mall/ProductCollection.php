<?php

namespace App\Http\Resources\Mall;

use App\Http\Resources\BaseCollection;

class ProductCollection extends BaseCollection
{
    public $collects = ProductListItemResource::class;
}
