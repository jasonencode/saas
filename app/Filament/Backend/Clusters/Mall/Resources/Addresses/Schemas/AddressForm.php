<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Addresses\Schemas;

use App\Enums\RegionLevel;
use App\Models\Region;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class AddressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->label('用户')
                    ->options(fn() => User::query()->pluck('username', 'id'))
                    ->searchable()
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->label('联系人')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('mobile')
                    ->label('手机号')
                    ->required()
                    ->maxLength(20),
                Group::make()
                    ->schema([
                        Forms\Components\Select::make('province_id')
                            ->label('省份')
                            ->options(fn() => Region::query()->where('level', RegionLevel::Province)->pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('city_id', null);
                                $set('district_id', null);
                            })
                            ->required(),
                        Forms\Components\Select::make('city_id')
                            ->label('城市')
                            ->options(fn(Get $get): Collection => Region::query()
                                ->where('parent_id', $get('province_id'))
                                ->pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(fn(Set $set) => $set('district_id', null))
                            ->required(),
                        Forms\Components\Select::make('district_id')
                            ->label('区县')
                            ->options(fn(Get $get): Collection => Region::query()
                                ->where('parent_id', $get('city_id'))
                                ->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('address')
                    ->label('详细地址')
                    ->required()
                    ->columnSpanFull()
                    ->rows(3),
                Forms\Components\Toggle::make('is_default')
                    ->label('设为默认')
                    ->default(false),
            ]);
    }
}
