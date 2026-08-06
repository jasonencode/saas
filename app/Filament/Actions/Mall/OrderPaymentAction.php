<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Finance\AccountAssetType;
use App\Enums\Mall\OrderStatus;
use App\Models\Finance\UserAccount;
use App\Models\Mall\Order;
use App\Services\Finance\UserAccountService;
use App\Services\Mall\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;
use Throwable;

class OrderPaymentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderPayment';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('订单付款');
        $this->icon(Heroicon::OutlinedBanknotes);
        $this->color('success');

        $this->visible(fn (Order $record): bool => userCan(self::getDefaultName(), $record) && $record->status === OrderStatus::Pending);

        $this->requiresConfirmation();
        $this->modalHeading('确认付款');
        $this->modalDescription('确认对订单进行付款么？直接从余额中扣除金额');
        $this->modalSubmitActionLabel('确认付款');

        $this->modalSubmitAction(function (Action $action): void {
            $record = $this->getRecord();
            $action->disabled(fn (): bool => !$record || !$this->hasEnoughBalance($record));
        });

        $this->schema([
            Schemas\Components\Grid::make()
                ->schema([
                    Infolists\Components\TextEntry::make('total_amount')
                        ->label('订单金额')
                        ->money('cny'),
                    Infolists\Components\TextEntry::make('available_balance')
                        ->label('可用余额')
                        ->color(fn (?Order $record): string => $record && $this->hasEnoughBalance($record) ? 'success' : 'danger')
                        ->state(function (Order $record): string {
                            $account = UserAccount::find($record->user_id);
                            $balance = $account?->balance ?? 0;

                            return '¥'.number_format($balance, 2, '.', '');
                        }),
                    Forms\Components\TextInput::make('payment_password')
                        ->label('支付密码')
                        ->required()
                        ->password()
                        ->dehydrated(false)
                        ->disabled(fn (?Order $record): bool => !$record || !$this->hasEnoughBalance($record))
                        ->hint(fn (?Order $record): string => $record && $this->hasEnoughBalance($record) ? '请输入支付密码' : '余额不足，无法付款')
                        ->hintColor(fn (?Order $record): string => $record && $this->hasEnoughBalance($record) ? 'gray' : 'danger')
                        ->columnSpanFull(),
                ]),
        ]);

        $this->action(function (Order $order): void {
            try {
                $account = UserAccount::find($order->user_id);
                if (!$account) {
                    throw new InvalidArgumentException('用户账户不存在');
                }

                $accountService = service(UserAccountService::class);
                $accountService->modifyAsset(
                    account: $account,
                    asset: AccountAssetType::Balance,
                    amount: -$order->getTotalAmount(),
                    remark: "订单# $order->no 付款",
                    source: $order
                );

                $orderService = service(OrderService::class);
                $orderService->pay($order, Filament::auth()->user());

                $this->successNotificationTitle('订单付款成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }

    protected function hasEnoughBalance(Order $record): bool
    {
        $account = UserAccount::find($record->user_id);

        return $account && bccomp((string) $account->balance, (string) $record->getTotalAmount(), 2) >= 0;
    }
}
