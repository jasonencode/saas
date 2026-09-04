<?php

namespace App\Http\Resources\Content;

use App\Http\Resources\BaseCollection;

class SinglePageCollection extends BaseCollection
{
    public $collects = SinglePageResource::class;
}
