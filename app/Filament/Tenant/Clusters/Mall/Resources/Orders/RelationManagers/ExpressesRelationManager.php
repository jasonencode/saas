<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\RelationManagers;

use App\Models\OrderShipping;
use App\Services\OrderService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpressesRelationManager extends RelationManager
{
    protected static string $relationship = 'expresses';

    protected static ?string $title = '发货记录';

    protected static ?string $modelLabel = '发货记录';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Forms\Components\Select::make('express_id')
                    ->label('快递名称')
                    ->relationship(
                        name: 'express',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->ofEnabled()
                    ),
                Forms\Components\TextInput::make('express_no')
                    ->label('快递单号')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('express.name')
                    ->label('快递名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('express_no')
                    ->label('快递单号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('收件人')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mobile')
                    ->label('手机号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_address')
                    ->label('完整地址'),
                Tables\Columns\TextColumn::make('delivery_at')
                    ->label('发货时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sign_at')
                    ->label('签收时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->recordActions([
                Actions\EditAction::make()
                    ->modalWidth(Width::Large),
                Actions\DeleteAction::make()
                    ->action(function (OrderShipping $record, OrderService $orderService): void {
                        $orderService->deleteExpress($record);
                    }),
            ]);
    }
}
