<?php

namespace App\Models\BlockChain;

use App\Enums\BlockChain\ContractDeployStatus;
use App\Enums\BlockChain\ContractType;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\Searchable;
use App\Policies\BlockChain\ContractPolicy;
use Illuminate\Database\Eloquent\Attributes\Scope;
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
        Searchable,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'deploy_status' => ContractDeployStatus::class,
            'type' => ContractType::class,
        ];
    }

    /**
     * 关联区块链网络
     *
     * @return BelongsTo<Network>
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class)
            ->withTrashed();
    }

    /**
     * 部署者地址
     *
     * @return BelongsTo<ChainAddress>
     */
    public function deployer(): BelongsTo
    {
        return $this->belongsTo(ChainAddress::class);
    }

    /**
     * 已部署的合约
     */
    #[Scope]
    protected function ofDeployed(Builder $query): void
    {
        $query->where('deploy_status', ContractDeployStatus::Deployed);
    }

    /**
     * 按合约类型筛选
     *
     * @param  ContractType  $type  合约类型
     */
    #[Scope]
    protected function ofType(Builder $query, ContractType $type): void
    {
        $query->where('type', $type);
    }
}
