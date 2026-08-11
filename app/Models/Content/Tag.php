<?php

namespace App\Models\Content;

use App\Enums\Content\TagType;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasSortable;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
abstract class Tag extends Model
{
    use BelongsToTenant,
        HasSortable,
        SoftDeletes;

    protected $table = 'tags';

    protected function casts(): array
    {
        return [
            'type' => TagType::class,
        ];
    }
}
