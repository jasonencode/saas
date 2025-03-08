<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected static function boot(): void
    {
        parent::boot();

        self::deleted(function(Attachment $attachment) {
            try {
                Storage::disk($attachment->disk)->delete($attachment->path);
            } catch (Exception) {
            }
        });
    }
}
