<?php

namespace App\Models;

use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

class Category extends Model
{
    use Cachable,
        HasCovers,
        HasEasyStatus,
        SoftDeletes;

    protected static function boot(): void
    {
        parent::boot();

        self::saving(static function(Category $category) {
            if (is_null($category->parent)) {
                $category->level = 1;
            } else {
                $category->level = $category->parent->level + 1;
            }
            if ($category->level > 3) {
                throw new RuntimeException('最多可以创建三级分类');
            }
        });

        self::deleting(static function(Category $category) {
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
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_category')
            ->withTimestamps();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__);
    }
}
