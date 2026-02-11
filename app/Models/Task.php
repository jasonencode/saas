<?php

namespace App\Models;

use App\Models\Traits\HasEasyStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasEasyStatus;

    protected $table = 'settlement_tasks';

    protected $casts = [
        'options' => 'json',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}