<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Mall\OrderStatus;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\UserAccount;
use App\Models\Mall\Order;
use App\Services\Finance\PaymentService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
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
                        ->disabled(fn (?Order $record): bool => !$record || !$this->canPay($record))
                        ->hint(fn (?Order $record): string => match (true) {
                            !$record => '',
                            !$this->hasEnoughBalance($record) => '余额不足，无法付款',
                            !$this->hasPaymentPassword($record) => '请先设置支付密码',
                            default => '请输入支付密码',
                        })
                        ->hintColor(fn (?Order $record): string => $record && $this->canPay($record) ? 'gray' : 'danger')
                        ->columnSpanFull(),
                ]),
        ]);

        $this->action(function (Order $order, array $data): void {
            try {
                // 建支付单、扣款与订单推进必须同一事务：任一失败则整体回滚，
                // 避免「余额已扣、订单未支付」或并发重复扣款；
                // 同时保证订单有 Paid 支付单，售后才有可退款的资金单据
                DB::transaction(function () use ($order, $data): void {
                    $lockedOrder = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

                    if ($lockedOrder->status !== OrderStatus::Pending) {
                        throw new InvalidArgumentException('订单状态已变更，请刷新后重试');
                    }

                    $payment = PaymentOrder::create([
                        'user_id' => $lockedOrder->user_id,
                        'tenant_id' => $lockedOrder->tenant_id,
                        'amount' => $lockedOrder->getTotalAmount(),
                        'gateway' => PaymentGateway::Balance,
                        'expired_at' => now()->addMinutes(30),
                        'paymentable' => $lockedOrder,
                    ]);

                    service(PaymentService::class)->payByBalance(
                        $payment,
                        $data['payment_password'],
                        Filament::auth()->user(),
                    );
                });

                $this->successNotificationTitle('订单付款成功');
                $this->success();
            } catch (InvalidArgumentException|RuntimeException $e) {
                $this->failureNotificationTitle('订单付款失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            } catch (Throwable $e) {
                report($e);

                $this->failureNotificationTitle('订单付款失败');
                $this->failureNotificationBody('系统异常，请稍后重试');
                $this->failure();
            }
        });
    }

    protected function hasEnoughBalance(Order $record): bool
    {
        $account = UserAccount::find($record->user_id);

        return $account && bccomp((string) $account->balance, (string) $record->getTotalAmount(), 2) >= 0;
    }

    protected function hasPaymentPassword(Order $record): bool
    {
        $account = UserAccount::find($record->user_id);

        return $account && $account->payment_password !== null;
    }

    protected function canPay(Order $record): bool
    {
        return $this->hasEnoughBalance($record) && $this->hasPaymentPassword($record);
    }
}
