<?php

namespace App\Filament\Actions\User;

use App\Models\User\User;
use App\Services\Finance\UserAccountService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Throwable;

class SetPaymentPasswordAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'setPaymentPassword';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('设置支付密码');
        $this->icon(Heroicon::OutlinedKey);
        $this->modalWidth(Width::Medium);

        $this->visible(fn (User $record): bool => userCan(self::getDefaultName(), $record));

        $this->schema([
            Forms\Components\TextInput::make('password')
                ->label('支付密码')
                ->required()
                ->password()
                ->maxLength(20),
            Forms\Components\TextInput::make('re_password')
                ->label('确认密码')
                ->required()
                ->password()
                ->maxLength(20)
                ->same('password'),
        ]);

        $this->action(function (User $record, array $data): void {
            try {
                $account = $record->account;

                if (!$account) {
                    throw new \InvalidArgumentException('用户账户不存在');
                }

                /** @var UserAccountService $accountService */
                $accountService = app(UserAccountService::class);
                $accountService->setPaymentPassword($account, $data['password']);

                $this->successNotificationTitle('支付密码设置成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('支付密码设置失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
