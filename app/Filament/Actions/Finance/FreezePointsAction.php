<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\AccountAssetType;
use App\Enums\User\UserAccountLogType;
use App\Filament\Actions\Concerns\ConfirmsCurrentPassword;
use App\Models\Finance\UserAccount;
use App\Services\Finance\UserAccountService;
use Deldius\UserField\UserEntry;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Throwable;

class FreezePointsAction extends Action
{
    use ConfirmsCurrentPassword;

    public static function getDefaultName(): ?string
    {
        return 'freezePoints';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('积分冻结/解冻');
        $this->icon(Heroicon::OutlinedLockClosed);
        $this->color('warning');

        $this->visible(fn (): bool => userCan(self::getDefaultName(), UserAccount::class));

        $this->modalWidth(Width::Large);

        $this->schema([
            UserEntry::make('user')
                ->label('用户账户'),
            Forms\Components\ToggleButtons::make('type')
                ->label('操作类型')
                ->inline()
                ->options([
                    UserAccountLogType::Freeze->value => '冻结',
                    UserAccountLogType::Unfreeze->value => '解冻',
                ])
                ->icons([
                    'freeze' => 'heroicon-m-lock-closed',
                    'unfreeze' => 'heroicon-m-lock-open',
                ])
                ->default(UserAccountLogType::Freeze->value)
                ->required(),
            Forms\Components\TextInput::make('amount')
                ->label('数量')
                ->required()
                ->numeric()
                ->minValue(0.01),
            Forms\Components\Textarea::make('remark')
                ->label('备注')
                ->required()
                ->rows(3),
            $this->getCurrentPasswordField(),
        ]);

        $this->action(function (UserAccount $record, array $data): void {
            $type = UserAccountLogType::from($data['type']);

            try {
                service(UserAccountService::class)
                    ->frozenAsset(
                        account: $record,
                        asset: AccountAssetType::Points,
                        amount: $data['amount'],
                        isFreeze: $type === UserAccountLogType::Freeze,
                        remark: $data['remark'],
                        source: Filament::auth()->user()
                    );

                $this->successNotificationTitle('操作成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('操作失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
