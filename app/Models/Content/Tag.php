<?php

namespace App\Models\Content;

use App\Enums\Content\TagType;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
abstract class Tag extends Model
{
    use BelongsToTenant,
        SoftDeletes;

    protected $table = 'tags';

    protected function casts(): array
    {
        return [
            'type' => TagType::class,
        ];
    }
}
