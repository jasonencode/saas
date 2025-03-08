<?php

namespace App\Models\Traits;

use App\Contracts\ShouldExamine;
use App\Enums\ExamineState;
use App\Models\Examine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasEasyExamine
{
    public static function bootHasEasyExamine(): void
    {
        self::created(function(Model&ShouldExamine $model) {
            $model->examines()->create();
        });

        self::deleted(function(Model&ShouldExamine $model) {
            $model->examines()->delete();
        });

        self::restored(function(Model&ShouldExamine $model) {
            $model->examines()->restore();
        });

        self::forceDeleted(function(Model&ShouldExamine $model) {
            $model->examines()->forceDelete();
        });
    }

    public function examines(): MorphMany
    {
        return $this->morphMany(Examine::class, 'target');
    }

    public function examine(): MorphOne
    {
        return $this->morphOne(Examine::class, 'target')
            ->latest();
    }

    public function getStateAttribute(): ?ExamineState
    {
        return $this->examines()->latest()->first()?->state;
    }
}
