<?php

namespace App\Models;

use App\Enums\Gender;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasCovers;
use App\Services\SensitiveService;

class UserInfo extends Model
{
    use BelongsToUser,
        HasCovers;

    public $incrementing = false;

    protected $primaryKey = 'user_id';

    protected string $coverField = 'avatar';

    protected string $defaultImage = '/images/avatar.svg';

    protected $casts = [
        'gender' => Gender::class,
        'birthday' => 'date',
    ];

    protected function setNicknameAttribute(string $value): void
    {
        $this->attributes['nickname'] = resolve(SensitiveService::class)->filter($value);
    }
}
