<?php

namespace App\Policies\Finance;

use App\Contracts\Attributes\PolicyName;
use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Enums\System\PolicyType;
use App\Models\Finance\PaymentOrder;

class PaymentOrderPolicy extends Policy
{
    protected string $modelName = '支付记录';

    protected string $groupName = '财务管理';

    #[PolicyName('列表', type: PolicyType::Page)]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('详情', type: PolicyType::Page)]
    public function view(Authenticatable $user, PaymentOrder $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
