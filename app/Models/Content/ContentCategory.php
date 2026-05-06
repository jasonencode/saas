<?php

namespace App\Models\Content;

use App\Enums\Content\CategoryType;
use App\Policies\Content\ContentCategoryPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[UsePolicy(ContentCategoryPolicy::class)]
class ContentCategory extends Category
{
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->type = CategoryType::Content;
        });

        static::addGlobalScope('content', function ($query) {
            $query->where('type', CategoryType::Content);
        });
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_category', 'category_id', 'content_id')
            ->withTimestamps();
    }
}
