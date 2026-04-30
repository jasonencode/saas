<?php

namespace App\Models\Foundation;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Policies\Foundation\AlipayPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(AlipayPolicy::class)]
class Alipay extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    public function getConfig(): array
    {
        return [];
    }
}
