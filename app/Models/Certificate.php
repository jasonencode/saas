<?php

namespace App\Models;

use App\Enums\CertificateSignType;
use App\Enums\CertificateType;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 证书模型
 */
#[Unguarded]
class Certificate extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    protected $casts = [
        'type' => CertificateType::class,
        'sign_type' => CertificateSignType::class,
    ];

    /**
     * 父级证书
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__);
    }

    /**
     * 子级证书
     */
    public function children(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    /**
     * 获取证书的DN信息
     */
    public function getDnAttribute(): array
    {
        return [
            'countryName' => $this->country_name,
            'stateOrProvinceName' => $this->state_or_province_name,
            'localityName' => $this->locality_name,
            'organizationName' => $this->organization_name,
            'organizationalUnitName' => $this->organizational_unit_name,
            'commonName' => $this->common_name,
            'emailAddress' => $this->email_address,
        ];
    }
}
