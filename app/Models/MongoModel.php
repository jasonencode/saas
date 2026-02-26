<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * 所有 MongoDB 模型的抽象基类。
 */
abstract class MongoModel extends Model
{
    protected $connection = 'mongodb';

    protected $guarded = [];
}