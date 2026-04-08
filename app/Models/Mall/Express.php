<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use App\Policies\ExpressPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 快递公司模型
 */
#[Unguarded]
#[UsePolicy(ExpressPolicy::class)]
class Express extends Model
{
    use HasCovers,
        HasEasyStatus,
        HasSortable,
        SoftDeletes;
}
