<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StafferTeam extends Pivot
{
    protected $table = 'team_user';

    public function staffer(): BelongsTo
    {
        return $this->belongsTo(Staffer::class);

    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}