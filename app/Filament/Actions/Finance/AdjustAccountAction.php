<?php

namespace App\Filament\Actions\Finance;

use App\Enums\Finance\AccountAssetType;
use App\Filament\Actions\Concerns\ConfirmsCurrentPassword;
use App\Models\Finance\UserAccount;
use App\Services\Finance\UserAccountService;
use Deldius\UserField\UserEntry;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas\Components\Group;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Throwable;

class AdjustAccountAction extends Action
{
    use ConfirmsCurrentPassword;

    public static function getDefaultName(): ?string
    {
        return 'adjustAccount';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('调账');
        $this->icon(Heroicon::OutlinedCurrencyYen);

        $this->visible(fn (): bool => userCan(self::getDefaultName(), UserAccount::class));

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
            $this->getCurrentPasswordField(),
        ]);

        $this->action(function (UserAccount $record, array $data): void {
            $amount = $data['amount'];
            if ($data['direction'] === 'sub') {
                $amount = -$amount;
            }

            /** @var AccountAssetType $asset */
            $asset = $data['asset'];

            try {
                service(UserAccountService::class)->modifyAsset(
                    account: $record,
                    asset: $asset,
                    amount: $amount,
                    remark: $data['remark'],
                    source: Filament::auth()->user()
                );

                $this->successNotificationTitle('调账成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('操作失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
                $this->halt();
            }
        });
    }
}
