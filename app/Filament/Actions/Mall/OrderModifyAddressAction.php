<?php

namespace App\Filament\Actions\Mall;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;

class OrderModifyAddressAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderModifyAddress';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('修改地址');
        $this->icon(Heroicon::OutlinedMapPin);
        $this->color('warning');
        $this->modalWidth('lg');

        $this->visible(fn (Order $order) => in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true));

        $this->fillForm(fn (Order $order) => [
            'name' => $order->address->name,
            'mobile' => $order->address->mobile,
            'province_id' => $order->address->province_id,
            'city_id' => $order->address->city_id,
            'district_id' => $order->address->district_id,
            'address' => $order->address->address,
        ]);

        $this->schema([
            Grid::make()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('收货人')
                        ->required(),
                    Forms\Components\TextInput::make('mobile')
                        ->label('手机号')
                        ->required(),
                ]),
            Grid::make(3)
                ->schema([
                    Forms\Components\Select::make('province_id')
                        ->label('省份')
                        ->relationship('address.province', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(),
                    Forms\Components\Select::make('city_id')
                        ->label('城市')
                        ->relationship('address.city', 'name', fn ($query, $get) => $query->where('parent_id', $get('province_id')))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(),
                    Forms\Components\Select::make('district_id')
                        ->label('区县')
                        ->relationship('address.district', 'name', fn ($query, $get) => $query->where('parent_id', $get('city_id')))
                        ->searchable()
                        ->preload()
                        ->required(),
                ]),
            Forms\Components\Textarea::make('address')
                ->label('详细地址')
                ->required()
                ->rows(2),
        ]);

        $this->action(function (Order $order, array $data, OrderService $service) {
            $service->modifyAddress($order, $data, Filament::auth()->user());

            $this->successNotificationTitle('收货地址已修改');
            $this->success();
        });
    }
}
