<?php

namespace App\Contracts;

use App\Enums\ExamineState;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface ShouldExamine
{
    public function examines(): MorphMany;

    public function getExamineTitleAttribute(): string;

    public function examineCallback(ExamineState $state, ?string $text = null): void;
}
