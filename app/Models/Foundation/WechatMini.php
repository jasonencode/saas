<?php

namespace App\Models\Foundation;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Policies\Foundation\WechatMiniPolicy;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Hidden(['app_secret'])]
#[Unguarded]
#[UsePolicy(WechatMiniPolicy::class)]
class WechatMini extends Model
{
    use BelongsToTenant,
        Cachable,
        HasEasyStatus,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'app_secret' => 'encrypted',
        ];
    }

    /**
     * 获取微信小程序配置
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return [

        ];
    }
}
