<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Deliveries\RelationManagers;

use App\Models\Mall\Region;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DeliveryRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'rules';

    protected static ?string $modelLabel = '特殊规则';

    protected static ?string $title = '特殊规则';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make([
                    Forms\Components\Select::make('province_id')
                        ->label('省份')
                        ->placeholder('选择省份')
                        ->options(fn () => Region::where('level', 'p')->pluck('name', 'id'))
                        ->searchable(),
                    Forms\Components\Select::make('city_id')
                        ->label('城市')
                        ->placeholder('选择城市')
                        ->options(fn (Get $get) => Region::where('parent_id', $get('province_id'))->pluck('name', 'id'))
                        ->searchable(),
                    Forms\Components\Select::make('district_id')
                        ->label('区县')
                        ->placeholder('选择区县')
                        ->options(fn (Get $get) => Region::where('parent_id', $get('city_id'))->pluck('name', 'id'))
                        ->searchable(),
                ])
                    ->columns(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('first')
                    ->label('首件/首重')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->minValue(0),
                Forms\Components\TextInput::make('first_fee')
                    ->label('首费(元)')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                Forms\Components\TextInput::make('additional')
                    ->label('续件/续重')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->minValue(0),
                Forms\Components\TextInput::make('additional_fee')
                    ->label('续费(元)')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                Forms\Components\TextInput::make('free_shipping_threshold')
                    ->label('包邮门槛(元)')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('region_name')
            ->columns([
                Tables\Columns\TextColumn::make('province.name')
                    ->label('省份')
                    ->default('-'),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('城市')
                    ->default('-'),
                Tables\Columns\TextColumn::make('district.name')
                    ->label('区县')
                    ->default('-'),
                Tables\Columns\TextColumn::make('first')
                    ->label('首件/首重'),
                Tables\Columns\TextColumn::make('first_fee')
                    ->label('首费(元)')
                    ->money('CNY'),
                Tables\Columns\TextColumn::make('additional')
                    ->label('续件/续重'),
                Tables\Columns\TextColumn::make('additional_fee')
                    ->label('续费(元)')
                    ->money('CNY'),
                Tables\Columns\TextColumn::make('free_shipping_threshold')
                    ->label('包邮门槛')
                    ->money('CNY'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }
}
