<?php

namespace App\Filament\Actions\Mall;

use App\Models\Express;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;

class ShipOrderAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'shipOrder';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('订单发货');
        $this->icon(Heroicon::OutlinedTruck);
        $this->modalWidth('md');
        $this->schema([
            Forms\Components\Select::make('express_id')
                ->label('发货物流')
                ->options(fn () => Express::ofEnabled()->pluck('name', 'id')),
            Forms\Components\TextInput::make('express_no'),
        ]);
        $this->action(function (Order $order, array $data) {
            $this->successNotificationTitle('发货成功');
            $this->success();
        });
    }
}