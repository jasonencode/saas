<?php

namespace App\Models\Foundation;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Policies\Foundation\WechatPolicy;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Hidden(['app_secret'])]
#[Unguarded]
#[UsePolicy(WechatPolicy::class)]
class Wechat extends Model
{
    use BelongsToTenant,
        Cachable,
        HasEasyStatus,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'app_secret' => 'encrypted',
            'is_connected' => 'boolean',
        ];
    }

    /**
     * 关联支付配置
     *
     * @return HasMany<WechatPayment>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(WechatPayment::class);
    }

    /**
     * 获取微信配置
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return [

        ];
    }
}
