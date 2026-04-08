<?php

namespace App\Filament\Actions\Mall;

use App\Models\Mall\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;

class OrderAddRemarkAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderAddRemark';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('商家备注');
        $this->icon(Heroicon::OutlinedChatBubbleBottomCenterText);
        $this->color('gray');
        $this->modalWidth('md');

        $this->fillForm(fn (Order $order) => [
            'seller_remark' => $order->seller_remark,
        ]);

        $this->schema([
            Forms\Components\Textarea::make('seller_remark')
                ->label('备注内容')
                ->rows(3)
                ->placeholder('请输入内部备注信息，仅商家可见'),
        ]);

        $this->action(function (Order $order, array $data, OrderService $service) {
            $service->addSellerRemark($order, $data['seller_remark'], Filament::auth()->user());

            $this->successNotificationTitle('备注已更新');
            $this->success();
        });
    }
}
