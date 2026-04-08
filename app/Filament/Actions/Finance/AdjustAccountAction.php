<?php

namespace App\Filament\Actions\Finance;

use App\Enums\User\AccountAssetType;
use App\Models\User\UserAccount;
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

class AdjustAccountAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'adjustAccount';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('调账');
        $this->icon(Heroicon::OutlinedCurrencyYen);
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
                    ->inline()
                    ->live(),
                Forms\Components\ToggleButtons::make('direction')
                    ->label('调整方向')
                    ->inline()
                    ->options([
                        'add' => '增加',
                        'sub' => '扣除',
                    ])
                    ->default('add')
                    ->required(),
            ])
                ->columns(),
            Forms\Components\TextInput::make('amount')
                ->label('调整数量')
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
            if ($data['direction'] === 'sub') {
                $amount = -$amount;
            }

            /** @var AccountAssetType $asset */
            $asset = $data['asset'];

            try {
                app(UserAccountService::class)->modifyAsset(
                    account: $record,
                    asset: $asset,
                    amount: $amount,
                    remark: $data['remark'],
                    source: Filament::auth()->user()
                );

                $this->successNotificationTitle('调账成功');
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
