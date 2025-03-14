<?php

namespace App\Filament\Backend\Clusters\Users\Resources;

use App\Filament\Backend\Clusters\Users;
use App\Filament\Backend\Clusters\Users\Resources\AddressResource\Pages;
use App\Filament\Tables\Components\SearchableUserColumn;
use App\Models\Address;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AddressResource extends Resource
{
    protected static ?string $model = Address::class;

    protected static ?string $modelLabel = '收货地址';

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = '用户地址';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = Users::class;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->columns([
                SearchableUserColumn::make(),
                Tables\Columns\TextColumn::make('name')
                    ->label('收件人姓名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mobile')
                    ->label('联系电话')
                    ->searchable(),
                Tables\Columns\TextColumn::make('province.name')
                    ->label('省'),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('市'),
                Tables\Columns\TextColumn::make('district.name')
                    ->label('区'),
                Tables\Columns\TextColumn::make('address')
                    ->label('详细地址'),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('默认地址'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\RestoreAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAddresses::route('/'),
        ];
    }
}
