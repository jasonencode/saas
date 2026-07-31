<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\Mall\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Throwable;

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
        $this->modalWidth(Width::Large);

        $this->visible(fn (Order $order): bool => userCan(self::getDefaultName(), $order) && $this->isModifiable($order));

        $this->fillForm(fn (Order $order): array => [
            'name' => $order->address->name,
            'mobile' => $order->address->mobile,
            'province_id' => $order->address->province_id,
            'city_id' => $order->address->city_id,
            'district_id' => $order->address->district_id,
            'address' => $order->address->address,
        ]);
        $this->schema([
            Schemas\Components\Grid::make(1)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('收货人')
                        ->required(),
                    Forms\Components\TextInput::make('mobile')
                        ->label('手机号')
                        ->required(),
                ]),
            Schemas\Components\Grid::make(3)
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
                        ->relationship('address.city', 'name', fn ($query, $get): mixed => $query->where('parent_id', $get('province_id')))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(),
                    Forms\Components\Select::make('district_id')
                        ->label('区县')
                        ->relationship('address.district', 'name', fn ($query, $get): mixed => $query->where('parent_id', $get('city_id')))
                        ->searchable()
                        ->preload()
                        ->required(),
                ]),
            Forms\Components\Textarea::make('address')
                ->label('详细地址')
                ->required()
                ->rows(2),
        ]);
        $this->action(function (Order $order, array $data, OrderService $service): void {
            try {
                $service->modifyAddress($order, $data, Filament::auth()->user());

                $this->successNotificationTitle('收货地址已修改');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }

    protected function isModifiable(Order $order): bool
    {
        return in_array($order->status, [
            OrderStatus::Paid,
            OrderStatus::Preparing,
        ], true);
    }
}
