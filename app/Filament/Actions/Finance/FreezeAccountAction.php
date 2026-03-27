<?php

namespace App\Filament\Actions\Finance;

use App\Enums\AccountAssetType;
use App\Enums\UserAccountLogType;
use App\Models\UserAccount;
use App\Services\UserAccountService;
use Deldius\UserField\UserEntry;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Throwable;

class FreezeAccountAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'freezeAccount';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('冻结/解冻');
        $this->icon(Heroicon::OutlinedLockClosed);
        $this->color('warning');
        $this->modalWidth(Width::Large);
        $this->schema([
            UserEntry::make('user')
                ->label('用户账户'),
            Group::make([
                Forms\Components\ToggleButtons::make('asset')
                    ->label('调整对象')
                    ->options(AccountAssetType::class)
                    ->default(AccountAssetType::Balance)
                    ->required()
                    ->inline(),
                Forms\Components\ToggleButtons::make('type')
                    ->label('操作类型')
                    ->inline()
                    ->options([
                        UserAccountLogType::Freeze->value => '冻结',
                        UserAccountLogType::Unfreeze->value => '解冻',
                    ])
                    ->default(UserAccountLogType::Freeze->value)
                    ->required(),
            ])
                ->columns(),

            Forms\Components\TextInput::make('amount')
                ->label('数量')
                ->required()
                ->numeric()
                ->minValue(0.01),
            Forms\Components\Textarea::make('remark')
                ->label('备注')
                ->required()
                ->rows(3),
            Forms\Components\TextInput::make('password')
                ->label('操作密码')
                ->required()
                ->password()
                ->dehydrated(false)
                ->currentPassword(),
        ]);

        $this->action(function (UserAccount $record, array $data) {
            $amount = $data['amount'];
            $type = UserAccountLogType::from($data['type']);
            /** @var AccountAssetType $asset */
            $asset = $data['asset'];

            try {
                app(UserAccountService::class)
                    ->frozenAsset(
                        account: $record,
                        asset: $asset,
                        amount: $amount,
                        isFreeze: $type === UserAccountLogType::Freeze,
                        remark: $data['remark'],
                        source: Filament::auth()->user()
                    );

                $this->successNotificationTitle('操作成功');
                $this->success();
            } catch (Throwable $e) {
                Notification::make()
                    ->title('操作失败')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();

                $this->halt();
            }
        });
    }
}
