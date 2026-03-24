<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Redpacks\RelationManagers;

use App\Enums\RedpackCodeStatus;
use App\Models\RedpackCode;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CodesRelationManager extends RelationManager
{
    protected static string $relationship = 'codes';

    protected static ?string $title = '红包码';

    protected static ?string $modelLabel = '红包码';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('amount')
                    ->label('金额')
                    ->required()
                    ->numeric()
                    ->prefix('￥')
                    ->suffix('元'),
                Forms\Components\Select::make('status')
                    ->label(__('backend.status'))
                    ->options(RedpackCodeStatus::class)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('红包码')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('金额')
                    ->money('CNY'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->badge(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('领取用户'),
                Tables\Columns\TextColumn::make('claimed_ip')
                    ->label('领取IP'),
                Tables\Columns\TextColumn::make('claimed_at')
                    ->label('领取时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('backend.status'))
                    ->options(RedpackCodeStatus::class),
            ])
            ->headerActions([
                $this->createCodeBulkAction(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    /**
     * 批量创建红包按钮
     */
    protected function createCodeBulkAction(): Actions\Action
    {
        return Actions\Action::make('bulkCreate')
            ->label('批量创建')
            ->color('primary')
            ->modalWidth('md')
            ->components([
                Forms\Components\TextInput::make('count')
                    ->label('生成数量')
                    ->integer()
                    ->required()
                    ->minValue(1)
                    ->maxValue(50000)
                    ->default(100),
                Forms\Components\TextInput::make('amount')
                    ->label('单个金额')
                    ->numeric()
                    ->required()
                    ->minValue(0.3)
                    ->suffix('元')
                    ->hintActions([
                        Actions\Action::make('quick_amounts_1')
                            ->label('1.88元')
                            ->action(function (Set $set) {
                                $set('amount', '1.88');
                            }),
                        Actions\Action::make('quick_amounts_2')
                            ->label('2.88元')
                            ->action(function (Set $set) {
                                $set('amount', '2.88');
                            }),
                        Actions\Action::make('quick_amounts_3')
                            ->label('3.88元')
                            ->action(function (Set $set) {
                                $set('amount', '3.88');
                            }),
                    ]),
            ])
            ->action(function (array $data) {
                $count = (int) $data['count'];
                $amount = $data['amount'];
                $redpackId = $this->getOwnerRecord()->getKey();

                for ($i = 0; $i < $count; $i++) {
                    RedpackCode::create([
                        'redpack_id' => $redpackId,
                        'amount' => $amount,
                        'status' => RedpackCodeStatus::Active,
                    ]);
                }

                Notification::make()
                    ->title('批量创建成功')
                    ->body("已成功为该活动生成 $count 个红包码。")
                    ->success()
                    ->send();
            });
    }
}
