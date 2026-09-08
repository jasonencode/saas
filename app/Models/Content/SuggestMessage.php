<?php

namespace App\Models\Content;

use App\Models\Model;
use App\Models\System\Administrator;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Unguarded]
class SuggestMessage extends Model
{
    /**
     * 所属反馈
     *
     * @return BelongsTo<Suggest>
     */
    public function suggest(): BelongsTo
    {
        return $this->belongsTo(Suggest::class);
    }

    /**
     * 发送者（多态关联）
     */
    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 是否来自用户
     */
    public function getIsFromUserAttribute(): bool
    {
        return $this->sender instanceof User;
    }

    /**
     * 是否来自管理员
     */
    public function getIsFromAdminAttribute(): bool
    {
        return $this->sender instanceof Administrator;
    }
}
