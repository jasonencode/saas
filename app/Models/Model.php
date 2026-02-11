<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * 基础模型
 */
abstract class Model extends Eloquent
{
    protected $guarded = [];
}
