<?php

namespace App\Models;

use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 快递公司模型
 */
#[Unguarded]
class Express extends Model
{
    use HasCovers,
        HasEasyStatus,
        HasSortable,
        SoftDeletes;
}
