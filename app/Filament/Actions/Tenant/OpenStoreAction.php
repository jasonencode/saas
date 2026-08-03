<?php

namespace App\Filament\Actions\Tenant;

use App\Models\Mall\StoreConfigure;
use App\Models\System\Tenant;
use App\Services\Mall\StoreService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class OpenStoreAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'openStore';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('开通商城');
        $this->icon(Heroicon::OutlinedShoppingBag);

        $this->visible(fn (Tenant $tenant): bool => userCan(self::getDefaultName(), $tenant));

        $this->requiresConfirmation();

        $this->modalHeading('开通商城');
        $this->modalDescription('确定要为该租户开通商城功能吗？请填写以下必填项。');

        $this->visible(fn (Tenant $tenant): bool => !StoreConfigure::isTenantOpened($tenant->getKey()));

        $this->schema([
            TextInput::make('store_name')
                ->label('店铺名称')
                ->required()
                ->maxLength(255)
                ->placeholder('请输入店铺名称'),
            Select::make('auto_complete_days')
                ->label('自动完成天数')
                ->required()
                ->options([
                    7 => '7天自动完成',
                    14 => '14天自动完成',
                    30 => '30天自动完成',
                ])
                ->default(7),
            TextInput::make('order_expired_minutes')
                ->label('订单自动取消时间')
                ->required()
                ->integer()
                ->minValue(1)
                ->default(10)
                ->maxValue(1440)
                ->suffix('分钟'),
        ]);

        $this->action(function (Tenant $tenant, array $data): void {
            service(StoreService::class)->openStore($tenant, $data);
            $this->successNotificationTitle('商城开通成功');
            $this->success();
        });
    }
}
