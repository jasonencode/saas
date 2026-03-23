<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        HasCovers,
        HasSortable,
        SoftDeletes;
}
