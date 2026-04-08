<?php

namespace App\Models\Content;

use App\Models\Model;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Policies\CommentPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 评论模型
 */
#[Unguarded]
#[UsePolicy(CommentPolicy::class)]
class Comment extends Model
{
    use BelongsToUser,
        HasCovers,
        HasEasyStatus,
        SoftDeletes;

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
