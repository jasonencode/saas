<?php

namespace App\Models;

use App\Contracts\ShouldExamine;
use App\Enums\ExamineState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;

class Examine extends Model
{
    use SoftDeletes;

    protected $casts = [
        'state' => ExamineState::class,
        'passed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * 操作用户
     *
     * @return MorphTo
     */
    public function reviewer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 审核对象
     *
     * @return MorphTo&ShouldExamine
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    public function pass(User $user, ?string $text = null): bool
    {
        if ($this->state !== ExamineState::Pending) {
            return false;
        }

        $this->reviewer_type = $user->getMorphClass();
        $this->reviewer_id = $user->getKey();
        $this->state = ExamineState::Approved;
        $this->pass_text = $text;
        $this->passed_at = now();

        if ($this->target instanceof ShouldExamine) {
            $this->target->examineCallback(ExamineState::Approved, $text);
        }

        return $this->save();
    }

    public function reject(User $user, ?string $text = null): bool
    {
        if ($this->state != ExamineState::Pending) {
            return false;
        }

        $this->reviewer_type = $user->getMorphClass();
        $this->reviewer_id = $user->getKey();
        $this->state = ExamineState::Rejected;
        $this->reject_text = $text;
        $this->rejected_at = now();

        if ($this->target instanceof ShouldExamine) {
            $this->target->examineCallback(ExamineState::Approved, $text);
        }

        return $this->save();
    }

    public function scopeOfPending(Builder $query): Builder
    {
        return $query->where('state', ExamineState::Pending);
    }

    public function scopeOfPassed(Builder $query): Builder
    {
        return $query->where('state', ExamineState::Approved);
    }

    public function scopeOfRejected(Builder $query): Builder
    {
        return $query->where('state', ExamineState::Rejected);
    }

    public function isPending(): bool
    {
        return $this->state === ExamineState::Pending;
    }

    public function isPassed(): bool
    {
        return $this->state === ExamineState::Approved;
    }

    public function isRejected(): bool
    {
        return $this->state !== ExamineState::Rejected;
    }
}
