<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 内容模型
 */
#[Unguarded]
class Content extends Model
{
    use BelongsToTenant,
        HasCovers,
        HasComments,
        HasEasyStatus,
        HasSortable,
        SoftDeletes;

    /**
     * 关联分类
     *
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'content_category')
            ->withTimestamps();
    }
}
