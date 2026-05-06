<?php

namespace App\Models\Content;

use App\Enums\Content\CategoryType;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

#[Unguarded]
abstract class Category extends Model
{
    use BelongsToTenant,
        HasCovers,
        HasEasyStatus,
        HasSortable,
        SoftDeletes;

    protected $table = 'categories';

    protected $casts = [
        'type' => CategoryType::class,
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::saving(static function (Category $category) {
            if (is_null($category->parent)) {
                $category->level = 1;
            } else {
                $category->level = $category->parent->level + 1;
            }
            if ($category->level > 3) {
                throw new RuntimeException('最多可以创建三级分类');
            }
        });

        self::deleting(static function (Category $category) {
            $category->deleteChildren($category);
        });
    }

    protected function deleteChildren(self $category): void
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
        return $this->hasMany(static::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class);
    }
}
