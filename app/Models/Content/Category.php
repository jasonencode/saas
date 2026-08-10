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

    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
        ];
    }

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

    /**
     * 递归删除子分类
     *
     * @param  self  $category  要删除子分类的父分类
     */
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

    /**
     * 子分类列表
     *
     * @return HasMany<self>
     */
    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    /**
     * 父分类
     *
     * @return BelongsTo<self, self>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class)
            ->withTrashed();
    }
}
