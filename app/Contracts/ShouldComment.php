<?php

namespace App\Contracts;

use App\Models\Content\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * 可评论模型接口
 */
interface ShouldComment
{
    /**
     * 关联评论
     *
     * @return MorphMany<Comment>
     */
    public function comments(): MorphMany;

    /**
     * 获取该模型标题
     */
    public function getCommentableTitleAttribute(): string;
}
