<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasRegion;
use App\Services\SensitiveService;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasRegion,
        BelongsToUser,
        SoftDeletes;

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public static function boot(): void
    {
        parent::boot();

        self::saved(function($model) {
            if ($model->is_default && $model->id) {
                Address::where('id', '<>', $model->id)
                    ->where('user_id', $model->user_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function setDefault(): bool
    {
        $this->is_default = true;

        return $this->save();
    }

    protected function setDistrictAttribute(Region $region): void
    {
        $this->attributes['province_id'] = $region->parent->parent->id;
        $this->attributes['city_id'] = $region->parent->id;
        $this->attributes['district_id'] = $region->id;
    }

    protected function getFullAddressAttribute(): string
    {
        return sprintf(
            '%s %s %s %s',
            $this->province->name,
            $this->city->name,
            $this->district->name,
            $this->address
        );
    }

    protected function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = app(SensitiveService::class)->filter($value);
    }

    protected function setAddressAttribute(string $value): void
    {
        $this->attributes['address'] = app(SensitiveService::class)->filter($value);
    }
}
