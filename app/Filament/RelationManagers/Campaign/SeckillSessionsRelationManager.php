<?php

namespace App\Filament\RelationManagers\Campaign;

use App\Enums\Campaign\SeckillSessionStatus;
use App\Filament\Actions\Campaign\SeckillSessionPreheatAction;
use App\Filament\Forms\Campaign\SeckillSessionForm;
use App\Filament\Infolists\Campaign\SeckillSessionInfolist;
use App\Models\Campaign\SeckillActivity;
use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * 秒杀活动下的场次
 *
 * 场次的查、增、改、删与上下线都在活动详情中完成，不再提供独立的场次资源。
 */
class SeckillSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $modelLabel = '秒杀场次';

    protected static ?string $title = '场次';

    /**
     * 允许在详情页的关联列表中创建、编辑和删除场次
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return SeckillSessionForm::configure($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return SeckillSessionInfolist::configure($schema);
    }

    public function table(Table $table): Table
    {
        /** @var SeckillActivity $activity */
        $activity = $this->getOwnerRecord();

        return $table
            ->defaultSort('start_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('租户')
                    ->visible(fn (): bool => filament()->getTenant() === null)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('activity.name')
                    ->label('活动')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('场次')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('商品')
                    ->limit(24),
                Tables\Columns\TextColumn::make('sku.name')
                    ->label('规格'),
                Tables\Columns\TextColumn::make('seckill_price')
                    ->label('秒杀价')
                    ->formatStateUsing(fn ($state): string => '¥'.number_format((float) $state, 2)),
                Tables\Columns\TextColumn::make('stock')
                    ->label('名额')
                    ->numeric(),
                Tables\Columns\TextColumn::make('sold')
                    ->label('已售')
                    ->numeric(),
                Tables\Columns\TextColumn::make('remaining')
                    ->label('剩余')
                    ->numeric(),
                Tables\Columns\TextColumn::make('start_at')
                    ->label('开抢时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_at')
                    ->label('结束时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options(SeckillSessionStatus::class),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'tenant_id' => $activity->tenant_id,
                        'status' => SeckillSessionStatus::Scheduled->value,
                    ]),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make(),
                    Actions\EditAction::make(),
                    SeckillSessionPreheatAction::make(),
                    Actions\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
