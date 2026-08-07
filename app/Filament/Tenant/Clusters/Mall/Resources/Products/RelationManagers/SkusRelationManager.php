<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Products\RelationManagers;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class SkusRelationManager extends RelationManager
{
    protected static string $relationship = 'skus';

    protected static ?string $modelLabel = 'SKU';

    protected static ?string $title = 'SKU';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Schemas\Components\Section::make('基本信息')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('规格名称')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->label('规格编号(69码)')
                            ->maxLength(32),
                        CustomUpload::cover('cover', '规格封面图'),
                        Forms\Components\TextInput::make('weight')
                            ->label('重量')
                            ->numeric()
                            ->step(0.01)
                            ->rule('decimal:0,2')
                            ->minValue(0)
                            ->default(0)
                            ->suffix('kg'),
                        Forms\Components\TextInput::make('volume')
                            ->label('体积')
                            ->numeric()
                            ->step(0.01)
                            ->rule('decimal:0,2')
                            ->minValue(0)
                            ->default(0)
                            ->suffix('m³'),
                        Forms\Components\TextInput::make('sort')
                            ->label(__('backend.sort'))
                            ->integer()
                            ->required()
                            ->default(0),
                    ]),
                Schemas\Components\Section::make('价格与库存')
                    ->icon(Heroicon::OutlinedCurrencyYen)
                    ->schema([
                        Schemas\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('origin_price')
                                    ->label('原价')
                                    ->numeric()
                                    ->step(0.01)
                                    ->rule('decimal:0,2')
                                    ->minValue(0)
                                    ->default(0)
                                    ->prefix('¥')
                                    ->suffix('元'),
                                Forms\Components\TextInput::make('price')
                                    ->label('销售价')
                                    ->numeric()
                                    ->step(0.01)
                                    ->rule('decimal:0,2')
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->prefix('¥')
                                    ->suffix('元'),
                                Forms\Components\TextInput::make('stock')
                                    ->label('库存')
                                    ->integer()
                                    ->required()
                                    ->minValue(0)
                                    ->default(0),
                                Forms\Components\TextInput::make('sale')
                                    ->label('销量')
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover')
                    ->label('规格图片'),
                Tables\Columns\TextColumn::make('name')
                    ->label('规格名称'),
                Tables\Columns\TextColumn::make('code')
                    ->label('规格编号(69码)'),
                Tables\Columns\TextColumn::make('origin_price')
                    ->label('原价(划线价)'),
                Tables\Columns\TextColumn::make('price')
                    ->label('销售价')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('库存')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('sale')
                    ->label('销量'),
                Tables\Columns\TextColumn::make('weight')
                    ->label('重量(KG)'),
                Tables\Columns\TextColumn::make('volume')
                    ->label('体积(m³)'),
                Tables\Columns\TextColumn::make('sort')
                    ->label(__('backend.sort')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
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
