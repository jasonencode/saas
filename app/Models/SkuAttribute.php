<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * SKU属性关联模型
 */
#[Unguarded]
class SkuAttribute extends Pivot
{
}
