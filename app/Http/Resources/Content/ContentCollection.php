<?php

namespace App\Http\Resources\Content;

use App\Http\Resources\BaseCollection;

class ContentCollection extends BaseCollection
{
    public $collects = ContentResource::class;
}
