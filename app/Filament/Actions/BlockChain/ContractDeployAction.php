<?php

namespace App\Filament\Actions\BlockChain;

use App\Models\BlockChain\Contract;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class ContractDeployAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'contractDeploy';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('部署合约');
        $this->icon(Heroicon::RocketLaunch);
        $this->color('primary');
        $this->requiresConfirmation();
        $this->visible(fn (Contract $contract): bool => !$contract->address);

        $this->action(function (Contract $contract): void {
            // TODO: 实际的链上部署逻辑
            $this->successNotificationTitle('合约部署任务已创建');
            $this->success();
        });
    }
}