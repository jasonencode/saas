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

    public function getCommentTitleAttribute(): string;
}
