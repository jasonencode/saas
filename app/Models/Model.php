<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * 基础模型
 *
 * @method disable() Disable the model instance
 * @method enable() Restore the model instance
 * @method bySort() Order by sort
 *
 * @property int $sort
 */
#[Unguarded]
abstract class Model extends Eloquent
{
}
