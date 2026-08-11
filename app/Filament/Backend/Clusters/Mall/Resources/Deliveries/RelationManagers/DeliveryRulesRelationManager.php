<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Deliveries\RelationManagers;

use App\Filament\Actions\Common\UpgradeSortAction;
use App\Models\Mall\Region;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                Section::make('配送区域')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('province_id')
                            ->label('省份')
                            ->placeholder('选择省份')
                            ->options(fn () => Region::where('level', 'p')->bySort()->pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('city_id')
                            ->label('城市')
                            ->placeholder('选择城市')
                            ->options(fn (Get $get) => Region::where('parent_id', $get('province_id'))->bySort()->pluck('name', 'id'))
                            ->searchable(),
                        Forms\Components\Select::make('district_id')
                            ->label('区县')
                            ->placeholder('选择区县')
                            ->options(fn (Get $get) => Region::where('parent_id', $get('city_id'))->bySort()->pluck('name', 'id'))
                            ->searchable(),
                    ]),
                Section::make('计费规则')
                    ->columns()
                    ->columnSpanFull()
                    ->schema([
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
                    ]),
                Section::make('其他设置')
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('free_shipping_threshold')
                            ->label('包邮门槛(元)')
                            ->default(0)
                            ->numeric()
                            ->minValue(0)
                            ->helperText('0 表示不包邮。')
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('sort')
                            ->label(__('backend.sort'))
                            ->integer()
                            ->required()
                            ->default(0)
                            ->helperText('数字越大越靠前'),
                        Forms\Components\Toggle::make('status')
                            ->label(__('backend.status'))
                            ->default(true),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->bySort())
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
                Tables\Columns\TextColumn::make('sort')
                    ->label(__('backend.sort')),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    UpgradeSortAction::make(),
                    Actions\EditAction::make(),
                    Actions\DeleteAction::make(),
                ]),
            ]);
    }
}
