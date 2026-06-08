<?php

namespace App\Models\BlockChain;

use App\Enums\BlockChain\ContractDeployStatus;
use App\Enums\BlockChain\ContractType;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Policies\BlockChain\ContractPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(ContractPolicy::class)]
class Contract extends Model
{
    use BelongsToTenant,
        SoftDeletes;

    protected $casts = [
        'deploy_status' => ContractDeployStatus::class,
        'type' => ContractType::class,
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function scopeOfDeployed(Builder $query): void
    {
        $query->where('deploy_status', ContractDeployStatus::Deployed);
    }

    public function scopeOfType(Builder $query, ContractType $type): void
    {
        $query->where('type', $type);
    }

    /**
     * 部署者地址
     */
    public function deployer(): BelongsTo
    {
        return $this->belongsTo(ChainAddress::class);
    }
}
