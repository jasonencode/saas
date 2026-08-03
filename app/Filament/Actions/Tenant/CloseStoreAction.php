<?php

namespace App\Filament\Actions\Tenant;

use App\Models\System\Tenant;
use App\Services\Mall\StoreService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class CloseStoreAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'closeStore';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('关闭商城');
        $this->icon(Heroicon::OutlinedShoppingBag);
        $this->color('danger');

        $this->visible(fn (Tenant $tenant): bool => userCan(self::getDefaultName(), $tenant) && $tenant->storeConfigure?->isOpened());

        $this->requiresConfirmation();

        $this->modalHeading('关闭商城');
        $this->modalDescription('确定要关闭该租户的商城功能吗？关闭后该租户将无法访问商城相关接口。');

        $this->action(function (Tenant $tenant): void {
            service(StoreService::class)->closeStore($tenant);

            $this->successNotificationTitle('商城已关闭');
            $this->success();
        });
    }
}
