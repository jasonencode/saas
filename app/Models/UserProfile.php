<?php

namespace App\Models;

use App\Enums\Gender;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasCovers;
use App\Services\SensitiveService;

/**
 * 用户资料模型
 */
class UserProfile extends Model
{
    use BelongsToUser,
        HasCovers;

    public $incrementing = false;

    protected $primaryKey = 'user_id';

    protected string $coverField = 'avatar';

    protected string $defaultImage = '/images/avatar.svg';

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
        $this->attributes['nickname'] = resolve(SensitiveService::class)->filter($value);
    }
}
