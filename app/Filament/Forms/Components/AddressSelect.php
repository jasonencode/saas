<?php

namespace App\Filament\Forms\Components;

use App\Enums\RegionLevel;
use App\Models\Region;
use Filament\Forms;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Collection;

class AddressSelect
{
    public static function make(): Group
    {
        return Group::make()
            ->columns(3)
            ->schema([
                Forms\Components\Select::make('province_id')
                    ->label('省份')
                    ->options(fn () => Regionwhere('level', RegionLevel::Province)->pluck('name', 'id'))
                    ->live()
                    ->afterStateUpdated(function (Set $set) {
                        $set('city_id', null);
                        $set('district_id', null);
                    })
                    ->required(),
                Forms\Components\Select::make('city_id')
                    ->label('城市')
                    ->options(fn (Get $get): Collection => Region::where('parent_id', $get('province_id'))
                        ->pluck('name', 'id'))
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('district_id', null))
                    ->required(),
                Forms\Components\Select::make('district_id')
                    ->label('区县')
                    ->options(fn (Get $get): Collection => Region::where('parent_id', $get('city_id'))
                        ->pluck('name', 'id'))
                    ->required(),
                Forms\Components\TextInput::make('address')
                    ->label('详细地址')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();

    }
}
