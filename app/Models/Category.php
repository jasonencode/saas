<?php

namespace App\Models;

use App\Enums\CategoryType;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;
use RuntimeException;

/**
 * 分类模型
 *
 * @module 内容/商城
 */
class Category extends Model
{
    use BelongsToTenant,
        HasCovers,
        HasEasyStatus,
        HasSortable,
        NodeTrait,
        SoftDeletes;

    protected $casts = [
        'type' => CategoryType::class,
    ];

    /**
     * 启动方法
     */
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
     * @param  Category  $category
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
     * 子分类
     *
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    /**
     * 父分类
     *
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__);
    }

    /**
     * 关联内容
     *
     * @return BelongsToMany
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_category')
            ->withTimestamps();
    }

    /**
     * 关联商品
     *
     * @return BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_category')
            ->using(ProductCategory::class)
            ->withTimestamps();
    }
}
