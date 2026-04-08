<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Regions\Schemas;

use App\Enums\Mall\RegionLevel;
use App\Models\Mall\Region;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class RegionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('id')
                    ->label('区划代码')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->columnSpanFull(),
                Forms\Components\Select::make('level')
                    ->label('地区级别')
                    ->options(RegionLevel::class)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('parent_id', null)),
                Forms\Components\Select::make('parent_id')
                    ->label('上级地区')
                    ->options(function (Get $get) {
                        $level = $get('level');
                        if (!$level) {
                            return [];
                        }
                        $level = $level instanceof RegionLevel ? $level : RegionLevel::from($level);

                        return match ($level) {
                            RegionLevel::Province => [0 => '无（顶级）'],
                            RegionLevel::City => Region::where('level', RegionLevel::Province)->orderBy('id')->pluck('name', 'id')->toArray(),
                            RegionLevel::District => Region::with('parent')
                                ->where('level', RegionLevel::City)
                                ->orderBy('parent_id')
                                ->orderBy('id')
                                ->get()
                                ->mapWithKeys(fn (Region $city) => [
                                    $city->id => ($city->parent?->name ? $city->parent->name.'-' : '').$city->name,
                                ])
                                ->toArray(),
                        };
                    })
                    ->searchable()
                    ->required()
                    ->disabled(fn (Get $get) => !$get('level'))
                    ->hint(fn (Get $get) => !$get('level') ? '请先选择地区级别' : null),
                Forms\Components\TextInput::make('name')
                    ->label('地区名称')
                    ->required()
                    ->maxLength(64),
                Forms\Components\TextInput::make('pinyin')
                    ->label('拼音')
                    ->maxLength(128)
                    ->nullable(),
                Forms\Components\TextInput::make('sort')
                    ->label(__('backend.sort'))
                    ->integer()
                    ->default(0)
                    ->minValue(0),
            ]);
    }
}
