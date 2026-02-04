<?php

namespace App\Policies;

use App\Contracts\Policy;

abstract class MallPolicy extends Policy
{
    protected string $groupName = '商城模块';
}