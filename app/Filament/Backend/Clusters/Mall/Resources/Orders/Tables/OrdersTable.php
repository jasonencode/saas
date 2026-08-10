<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Orders\Tables;

use App\Filament\Tables\Components\UserInfoColumn;
use App\Filament\Tables\Filters\TenantFilter;
use App\Models\Mall\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('no')
                    ->label('订单编号')
                    ->searchable(),
                UserInfoColumn::make(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('SPU数')
                    ->numeric(),
                Tables\Columns\TextColumn::make('items_quantity')
                    ->label('SKU数')
                    ->numeric(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('订单总额')
                    ->money('cny')
                    ->description(fn (Order $record) => '￥'.$record->amount.' / 运费:￥'.$record->freight)
                    ->color('primary'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->description(fn (Order $record) => $record->expired_at)
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('支付时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                TenantFilter::make(),
                DateRangeFilter::make('created_at')
                    ->teleport()
                    ->timePicker()
                    ->timePicker24()
                    ->timePickerSecond()
                    ->timePickerIncrement()
                    ->allowInput()
                    ->format('Y-m-d H:i:s')
                    ->label('下单时间'),
                DateRangeFilter::make('paid_at')
                    ->teleport()
                    ->timePicker()
                    ->timePicker24()
                    ->timePickerSecond()
                    ->timePickerIncrement()
                    ->allowInput()
                    ->format('Y-m-d H:i:s')
                    ->label('支付时间'),
                Tables\Filters\TrashedFilter::make(),
            ]);
    }
}
