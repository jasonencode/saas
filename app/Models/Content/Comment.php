<?php

namespace App\Models\Content;

use App\Models\Model;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Policies\Content\CommentPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
     * @return MorphTo<Model>
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 设置评论所属模型
     *
     * @param  Model  $model  评论所属模型
     */
    public function setCommentableAttribute(Model $model): void
    {
        $this->attributes['commentable_type'] = $model->getMorphClass();
        $this->attributes['commentable_id'] = $model->getKey();
    }
}
