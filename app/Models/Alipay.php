<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alipay extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;
}
