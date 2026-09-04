<?php

namespace App\Http\Resources\Content;

use App\Http\Resources\BaseCollection;

class CommentCollection extends BaseCollection
{
    public $collects = CommentResource::class;
}
