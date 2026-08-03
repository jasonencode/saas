<?php

namespace App\Models\BlockChain;

use App\Models\Model;
use App\Models\Traits\HasEasyStatus;
use App\Policies\BlockChain\ContractRepositoryPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(ContractRepositoryPolicy::class)]
class ContractRepository extends Model
{
    use HasEasyStatus,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'tags' => AsArrayObject::class,
        ];
    }
}
