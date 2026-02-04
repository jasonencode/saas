<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SkuAttribute extends Pivot
{
    protected $table = 'mall_sku_attribute';
}
