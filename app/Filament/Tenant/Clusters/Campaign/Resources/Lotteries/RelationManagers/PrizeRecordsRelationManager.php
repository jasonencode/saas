<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Lotteries\RelationManagers;

use App\Enums\Campaign\LotteryPrizeStatus;
use App\Enums\Campaign\LotteryPrizeType;
use App\Services\Campaign\LotteryService;
use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;

class PrizeRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'prizeRecords';

    protected static ?string $title = '中奖记录';

    protected static ?string $modelLabel = '中奖记录';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('用户'),
                Tables\Columns\TextColumn::make('prize.name')
                    ->label('奖品'),
                Tables\Columns\TextColumn::make('type')
                    ->label('奖品类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge(),
                Tables\Columns\TextColumn::make('fulfillment_note')
                    ->label('兑奖备注')
                    ->limit(30),
                Tables\Columns\TextColumn::make('fulfilled_at')
                    ->label('兑奖时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('奖品类型')
                    ->options(LotteryPrizeType::class)
                    ->exclude(LotteryPrizeType::None),
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options(LotteryPrizeStatus::class),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('fulfill')
                        ->label('批量兑奖')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('确认兑奖')
                        ->modalDescription('将选中的实物奖品标记为已兑奖')
                        ->action(function ($records) {
                            $service = App::make(LotteryService::class);

                            foreach ($records as $record) {
                                try {
                                    $service->fulfillPrize($record);
                                } catch (InvalidArgumentException $e) {
                                    // 跳过不可兑奖的记录
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
