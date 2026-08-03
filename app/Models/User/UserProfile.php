<?php

namespace App\Models\User;

use App\Enums\User\Gender;
use App\Models\Model;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasCovers;
use App\Services\System\SensitiveService;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;

#[Unguarded]
#[Table(key: 'user_id')]
#[WithoutIncrementing]
class UserProfile extends Model
{
    use BelongsToUser,
        HasCovers;

    protected string $coverField = 'avatar';

    protected string $defaultImage = '/images/avatar.jpg';

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'gender' => Gender::class,
        ];
    }

    /**
     * 设置昵称（敏感词过滤）
     *
     * @param  string  $value  昵称
     */
    protected function setNicknameAttribute(string $value): void
    {
        $this->attributes['nickname'] = service(SensitiveService::class)->filter($value);
    }
}
