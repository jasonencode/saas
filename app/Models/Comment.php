<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 评论模型
 */
class Comment extends Model
{
    use BelongsToUser,
        HasCovers,
        HasEasyStatus;

    /**
     * 评论所属模型
     *
     * @return MorphTo
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
