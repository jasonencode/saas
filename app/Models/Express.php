<?php

namespace App\Models;

use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 快递公司模型
 *
 * @module 商城
 */
class Express extends Model
{
    use Cachable,
        HasCovers,
        HasEasyStatus,
        HasSortable,
        SoftDeletes;
}
