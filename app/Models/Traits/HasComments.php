<?php

namespace App\Models\Traits;

use App\Models\Content\Comment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * 评论关联
 *
 * @property-read Collection<int, Comment> $comments
 */
trait HasComments
{
    /**
     * 评论关联
     *
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
