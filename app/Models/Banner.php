<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 轮播图模型
 */
#[Unguarded]
class Banner extends Model
{
    use BelongsToTenant,
        HasCovers,
        HasSortable,
        HasEasyStatus,
        SoftDeletes;
}
