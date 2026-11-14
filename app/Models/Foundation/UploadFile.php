<?php

namespace App\Models\Foundation;

use App\Models\Model;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * 上传文件记录
 *
 * 记录通过 API 上传的文件元信息，文件实体存放在对应磁盘。
 */
#[Unguarded]
class UploadFile extends Model
{
    use BelongsToUser,
        HasUuids;

    /**
     * 是否维护 created_at / updated_at
     */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }
}
