<?php

namespace App\Filament\Actions\Campaign;

use App\Enums\RedpackCodeStatus;
use App\Models\Redpack;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Set;
use Livewire\Component;

class CreateCodeBulkAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'createCodeBulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量创建');
        $this->color('primary');
        $this->modalWidth('md');
        $this->schema([
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
                    Action::make('quick_amounts_1')
                        ->label('1.88元')
                        ->action(function (Set $set) {
                            $set('amount', '1.88');
                        }),
                    Action::make('quick_amounts_2')
                        ->label('2.88元')
                        ->action(function (Set $set) {
                            $set('amount', '2.88');
                        }),
                    Action::make('quick_amounts_3')
                        ->label('3.88元')
                        ->action(function (Set $set) {
                            $set('amount', '3.88');
                        }),
                ]),
        ]);

        $this->action(function (array $data, Component $livewire) {
            $count = (int) $data['count'];
            $amount = $data['amount'];
            /** @var Redpack $redpack */
            $redpack = $livewire->getOwnerRecord();

            for ($i = 0; $i < $count; $i++) {
                $redpack->codes()->create([
                    'amount' => $amount,
                    'status' => RedpackCodeStatus::Active,
                ]);
            }

            $this->successNotificationTitle("已成功为该活动生成 $count 个红包码。");
            $this->success();
        });
    }
}