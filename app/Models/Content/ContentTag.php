<?php

namespace App\Models\Content;

use App\Enums\Content\TagType;
use App\Policies\Content\ContentTagPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[UsePolicy(ContentTagPolicy::class)]
class ContentTag extends Tag
{
    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (self $model) {
            $model->type = TagType::Content;
        });

        static::addGlobalScope('content', static function (Builder $query) {
            $query->where('type', TagType::Content);
        });
    }

    /**
     * 移除内容标签全局 scope
     *
     * @return Builder 不带内容标签 scope 的查询构造器
     */
    public static function withoutContentScope(): Builder
    {
        return static::withoutGlobalScope('content');
    }

    /**
     * 关联内容
     *
     * @return BelongsToMany<Content>
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_tag', 'tag_id', 'content_id');
    }
}
