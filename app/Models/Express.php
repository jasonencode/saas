<?php

namespace App\Models;

use App\Models\Model;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Express extends Model
{
    use Cachable,
        HasCovers,
        HasEasyStatus,
        HasSortable,
        SoftDeletes;

    protected $table = 'mall_expresses';
}
