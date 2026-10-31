<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Refund;
use App\Services\Mall\RefundService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Throwable;

class UpdateRefundAmountAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'updateRefundAmount';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('修改退款金额');
        $this->icon(Heroicon::OutlinedCurrencyYen);
        $this->color('warning');

        $this->visible(fn (Refund $refund): bool => userCan(self::getDefaultName(), $refund)
            && in_array($refund->status, [RefundStatus::Pending, RefundStatus::WaitingReturn], true));

        $this->fillForm(fn (Refund $refund): array => [
            'goods_amount' => (float) $refund->goods_amount,
            'freight_amount' => (float) $refund->freight_amount,
        ]);

        $this->schema([
            Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('goods_amount')
                        ->label('商品退款金额')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('￥')
                        ->required(),
                    Forms\Components\TextInput::make('freight_amount')
                        ->label('退款运费')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('￥')
                        ->required(),
                ]),
        ]);

        $this->action(function (Refund $refund, array $data): void {
            try {
                service(RefundService::class)->updateRefundAmount(
                    refund: $refund,
                    user: Filament::auth()->user(),
                    goodsAmount: (float) $data['goods_amount'],
                    freightAmount: (float) $data['freight_amount'],
                );

                $this->successNotificationTitle('退款金额已修改');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('修改退款金额失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
