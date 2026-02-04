<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    use BelongsToUser,
        HasCovers,
        HasEasyStatus;

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
