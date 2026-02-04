<?php

namespace App\Models;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use BelongsToTenant,
        Cachable,
        HasCovers,
        HasSortable,
        HasEasyStatus,
        SoftDeletes;

    protected $table = 'mall_banners';
}
