<?php

namespace App\Models;

use App\Enums\Gender;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasCovers;
use App\Services\SensitiveService;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;

/**
 * 用户资料模型
 */
#[Unguarded]
#[Table(key: 'user_id', incrementing: false)]
class UserProfile extends Model
{
    use BelongsToUser,
        HasCovers;

    protected string $coverField = 'avatar';

    protected string $defaultImage = '/images/avatar.jpg';

    protected $casts = [
        'birthday' => 'date',
        'gender' => Gender::class,
    ];

    /**
     * 设置昵称（敏感词过滤）
     *
     * @param  string  $value  昵称
     * @return void
     */
    protected function setNicknameAttribute(string $value): void
    {
        $this->attributes['nickname'] = service(SensitiveService::class)->filter($value);
    }
}
