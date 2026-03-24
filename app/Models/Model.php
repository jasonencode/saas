<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * 基础模型
 */
#[Unguarded]
abstract class Model extends Eloquent
{
}
