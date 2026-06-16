<?php

namespace App\Models\BlockChain;

use App\Enums\BlockChain\CertificateSignType;
use App\Enums\BlockChain\CertificateType;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Policies\BlockChain\CertificatePolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(CertificatePolicy::class)]
class Certificate extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    protected $casts = [
        'type' => CertificateType::class,
        'sign_type' => CertificateSignType::class,
        'password' => 'hashed',
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
