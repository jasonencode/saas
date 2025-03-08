<?php

namespace App\Models;

use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use Exception;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use Cachable,
        HasCovers,
        HasEasyStatus,
        SoftDeletes;

    protected static function boot(): void
    {
        parent::boot();

        self::saving(function (Category $category) {
            if ($category->parent == null) {
                $category->level = 1;
            } else {
                $category->level = $category->parent->level + 1;
            }
            if ($category->level > 3) {
                throw new Exception('最多可以创建三级分类');
            }
        });

        self::deleting(function (Category $category) {
            $category->deleteChildren($category);
        });
    }

    protected function deleteChildren(Category $category): void
    {
        if ($category->children()->count()) {
            foreach ($category->children ?? [] as $item) {
                if ($item->children()->count()) {
                    $this->deleteChildren($item);
                }
                $item->delete();
            }
        }
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_category')
            ->withTimestamps();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}