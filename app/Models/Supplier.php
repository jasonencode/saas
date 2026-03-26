<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 供应商模型
 */
#[Unguarded]
class Supplier extends Model
{
    use BelongsToTenant,
        HasCovers,
        HasEasyStatus,
        HasSortable,
        SoftDeletes;
}
