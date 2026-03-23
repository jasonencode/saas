<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\ReturnAddresses\Schemas;

use App\Enums\RegionLevel;
use App\Filament\Forms\Components\TenantSelect;
use App\Models\Region;
use Filament\Forms;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class ReturnAddressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantSelect::make()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->label('收件人')
                    ->required(),
                Forms\Components\TextInput::make('phone')
                    ->label('联系电话')
                    ->required(),
                Group::make()
                    ->schema([
                        Forms\Components\Select::make('province_id')
                            ->label('省份')
                            ->options(fn () => Region::query()->where('level', RegionLevel::Province)->pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('city_id', null);
                                $set('district_id', null);
                            })
                            ->required(),
                        Forms\Components\Select::make('city_id')
                            ->label('城市')
                            ->options(fn (Get $get): Collection => Region::query()
                                ->where('parent_id', $get('province_id'))
                                ->pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('district_id', null))
                            ->required(),
                        Forms\Components\Select::make('district_id')
                            ->label('区县')
                            ->options(fn (Get $get): Collection => Region::query()
                                ->where('parent_id', $get('city_id'))
                                ->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('address')
                    ->label('详细地地址')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_default')
                    ->label('默认地址'),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status')),
                Forms\Components\Textarea::make('remark')
                    ->label('备注信息')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
