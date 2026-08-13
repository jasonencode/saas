<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Express;
use App\Models\Mall\Refund;
use App\Models\Mall\RefundItem;
use App\Models\Mall\ReturnAddress;
use App\Services\Mall\RefundService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Throwable;

class ShipReturnAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'shipReturn';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('提交退货物流');
        $this->icon(Heroicon::OutlinedTruck);
        $this->color('info');

        $this->visible(fn (Refund $refund): bool => userCan(self::getDefaultName(), $refund) && $refund->status === RefundStatus::WaitingReturn);

        $this->requiresConfirmation();
        $this->modalHeading('提交退货物流');
        $this->modalDescription('请填写退货物流信息，退货请寄送至以下地址');

        $this->schema([
            Schemas\Components\Fieldset::make('退货地址')
                ->columns(1)
                ->schema([
                    Infolists\Components\TextEntry::make('return_address')
                        ->hiddenLabel()
                        ->state(fn (Refund $refund): HtmlString => self::getReturnAddressText($refund))
                        ->copyable()
                        ->placeholder('该退款商品未配置退货地址，请联系商家确认'),
                ]),
            Select::make('express_id')
                ->label('快递公司')
                ->options(Express::ofEnabled()->bySort()->pluck('name', 'id'))
                ->required()
                ->searchable(),
            TextInput::make('express_no')
                ->label('物流单号')
                ->required()
                ->maxLength(32),
        ]);
        $this->action(function (Refund $refund, array $data): void {
            try {
                service(RefundService::class)
                    ->shipReturn($refund, Filament::auth()->user(), $data);

                $this->successNotificationTitle('物流信息已提交');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }

    /**
     * 获取退款商品关联的退货地址文案
     *
     * 通过退款明细关联订单项的可订购主体（SKU），再取商品配置的退货地址。
     * 同一退款单涉及多个商品时，按去重后的地址分段展示。
     *
     * @param  Refund  $refund  退款单
     *
     * @return HtmlString 退货地址 HTML，无地址时返回空 HTML
     */
    private static function getReturnAddressText(Refund $refund): HtmlString
    {
        $addresses = $refund->items
            ->load('orderItem.orderable.product.returnAddress')
            ->map(fn (RefundItem $item) => $item->orderItem?->orderable?->product?->returnAddress)
            ->filter()
            ->unique('id')
            ->values();

        $blocks = $addresses
            ->map(fn (ReturnAddress $address): string => sprintf(
                '<div class="space-y-1"><div><span class="text-gray-500">收件人：</span>%s</div>'
                .'<div><span class="text-gray-500">联系电话：</span>%s</div>'
                .'<div><span class="text-gray-500">详细地址：</span>%s</div></div>',
                e($address->name),
                e($address->phone),
                e($address->full_address),
            ))
            ->implode('<div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>');

        return new HtmlString($blocks);
    }
}
