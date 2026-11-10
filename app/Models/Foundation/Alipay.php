<?php

namespace App\Models\Foundation;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Policies\Foundation\AlipayPolicy;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Hidden(['private_key'])]
#[Unguarded]
#[UsePolicy(AlipayPolicy::class)]
class Alipay extends Model
{
    use BelongsToTenant,
        Cachable,
        HasEasyStatus,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'private_key' => 'encrypted',
        ];
    }

    /**
     * 获取支付宝配置
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return [];
    }
}
