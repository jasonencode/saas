<?php

namespace App\Filament\Actions\Mall;

use App\Filament\Tenant\Clusters\Mall\Resources\Refunds\RefundResource;
use App\Models\Mall\Order;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class OrderRefundAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderRefund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('退款');
        $this->icon(Heroicon::OutlinedArrowUturnLeft);
        $this->color('warning');

        // 权限和状态检查统一由 Policy 处理
        $this->visible(fn (Order $record): bool => userCan(self::getDefaultName(), $record));

        $this->url(fn (Order $record): string => RefundResource::getUrl('create', ['order_no' => $record]));
    }
}
