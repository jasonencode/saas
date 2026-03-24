<?php

namespace App\Filament\Actions\Finance;

use App\Enums\AccountAssetType;
use App\Enums\UserAccountLogType;
use App\Models\UserAccount;
use Deldius\UserField\UserEntry;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Group;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

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
                    ->label('调整对象A')
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

            $field = $asset->getField();
            $frozenField = match ($asset) {
                AccountAssetType::Balance => 'frozen_balance',
                AccountAssetType::Points => 'frozen_points',
            };

            if ($type === UserAccountLogType::Freeze) {
                if ($record->$field < $amount) {
                    Notification::make()
                        ->title('操作失败')
                        ->body(($asset === AccountAssetType::Balance ? '可用余额' : '可用积分').'不足')
                        ->danger()
                        ->send();
                    $this->halt();
                }
            } elseif ($record->$frozenField < $amount) {
                Notification::make()
                    ->title('操作失败')
                    ->body(($asset === AccountAssetType::Balance ? '冻结余额' : '冻结积分').'不足')
                    ->danger()
                    ->send();
                $this->halt();
            }

            DB::transaction(static function () use ($record, $amount, $asset, $type, $field, $frozenField, $data) {
                $before = $record->$field;

                if ($type === UserAccountLogType::Freeze) {
                    $record->decrement($field, $amount);
                    $record->increment($frozenField, $amount);
                    $logAmount = -$amount;
                } else {
                    $record->decrement($frozenField, $amount);
                    $record->increment($field, $amount);
                    $logAmount = $amount;
                }

                $record->refresh();
                $after = $record->$field;

                $record->logs()->create([
                    'type' => $type,
                    'asset' => $asset,
                    'amount' => $logAmount,
                    'before' => $before,
                    'after' => $after,
                    'remark' => $data['remark'],
                    'source_type' => Filament::auth()->user()?->getMorphClass(),
                    'source_id' => Filament::auth()->id(),
                    'extra' => [
                        'frozen_before' => $type === UserAccountLogType::Freeze ? $record->$frozenField - $amount : $record->$frozenField + $amount,
                        'frozen_after' => $record->$frozenField,
                    ],
                ]);
            });

            $this->successNotificationTitle('操作成功');
            $this->success();
        });
    }
}
