<?php

namespace App\Models\Content;

use App\Enums\Content\SuggestStatus;
use App\Enums\Content\SuggestType;
use App\Models\Model;
use App\Models\Traits\BelongsToUser;
use App\Policies\Content\SuggestPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Unguarded]
#[UsePolicy(SuggestPolicy::class)]
class Suggest extends Model
{
    use BelongsToUser;

    protected $casts = [
        'type' => SuggestType::class,
        'status' => SuggestStatus::class,
    ];

    /**
     * 反馈消息列表
     *
     * @return HasMany<SuggestMessage>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SuggestMessage::class);
    }

    /**
     * 最后一条消息时间
     */
    public function getLastMessageAtAttribute(): Carbon
    {
        return $this->messages()->latest('created_at')
            ->value('created_at');
    }
}
