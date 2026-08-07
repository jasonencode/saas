<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\RelationManagers;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Models\Mall\OrderShipping;
use App\Services\Mall\OrderService;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShippingsRelationManager extends RelationManager
{
    protected static string $relationship = 'shippings';

    protected static ?string $title = '发货记录';

    protected static ?string $modelLabel = '发货记录';

    public function isReadOnly(): bool
    {
        return false;
    }

    /**
     * 发货记录是否可编辑/删除
     *
     * 仅在订单仍处于可发货状态（待发货/备货中/部分发货）时允许操作；
     * 已签收、已完成、已取消后锁定发货记录。
     */
    protected function isShippingsEditable(): bool
    {
        /** @var Order $order */
        $order = $this->getOwnerRecord();

        return in_array($order->status, [
            OrderStatus::Delivered,
            OrderStatus::PartiallyShipped,
        ], true);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Forms\Components\Select::make('express_id')
                    ->label('快递名称')
                    ->required()
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
                    ->modalWidth(Width::Large)
                    ->visible(fn (Actions\EditAction $action): bool => $this->isShippingsEditable()),
                Actions\DeleteAction::make()
                    ->visible(fn (Actions\DeleteAction $action): bool => $this->isShippingsEditable())
                    ->action(function (OrderShipping $record, OrderService $orderService): void {
                        $orderService->deleteExpress($record, Filament::auth()->user());
                    }),
            ]);
    }
}
