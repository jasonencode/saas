<?php

namespace App\Filament\Forms\Components;

use App\Enums\Mall\RegionLevel;
use App\Models\Mall\Region;
use Filament\Forms;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Contracts\Database\Eloquent\Builder;
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
                    ->options(fn () => Region::where('level', RegionLevel::Province)->orderby('sort')->pluck('name', 'id'))
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Region::where('level', RegionLevel::Province)
                        ->where(fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('pinyin', 'like', "%{$search}%"))
                        ->orderby('sort')
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->toArray())
                    ->getOptionLabelUsing(fn (string $value): string => Region::find($value)?->name ?? $value)
                    ->live()
                    ->afterStateUpdated(function (Set $set) {
                        $set('city_id', null);
                        $set('district_id', null);
                    })
                    ->required(),
                Forms\Components\Select::make('city_id')
                    ->label('城市')
                    ->options(fn (Get $get): Collection => Region::where('parent_id', $get('province_id'))->orderby('sort')->pluck('name', 'id'))
                    ->placeholder('请先选择省份')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search, Get $get): array => Region::where('parent_id', $get('province_id'))
                        ->where(fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('pinyin', 'like', "%{$search}%"))
                        ->orderby('sort')
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->toArray())
                    ->getOptionLabelUsing(fn (string $value): string => Region::find($value)?->name ?? $value)
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('district_id', null))
                    ->required(),
                Forms\Components\Select::make('district_id')
                    ->label('区县')
                    ->options(fn (Get $get): Collection => Region::where('parent_id', $get('city_id'))->orderby('sort')->pluck('name', 'id'))
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search, Get $get): array => Region::where('parent_id', $get('city_id'))
                        ->where(fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('pinyin', 'like', "%{$search}%"))
                        ->orderby('sort')
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->toArray())
                    ->getOptionLabelUsing(fn (string $value): string => Region::find($value)?->name ?? $value)
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
