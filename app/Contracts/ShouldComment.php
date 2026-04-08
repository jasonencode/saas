<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface ShouldComment
{
    /**
     * 获取与该模型相关的评论
     *
     * @return MorphMany
     */
    public function comments(): MorphMany;

    /**
     * 获取该模型标题
     *
     * @return string
     */
    public function getCommentableTitleAttribute(): string;
}
