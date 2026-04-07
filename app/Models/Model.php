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
 * @property User $user
 */
#[Unguarded]
class Model extends Eloquent
{
    /**
     * 分页数量
     *
     * @var int
     */
    protected $perPage = 10;
}
