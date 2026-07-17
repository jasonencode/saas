<?php

namespace App\Filament\Actions\Campaign;

use App\Models\Campaign\Redpack;
use App\Services\Campaign\RedpackService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;

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

        $this->visible(fn (Redpack $redpack): bool => userCan(self::getDefaultName(), $redpack));

        $this->modalWidth(Width::Medium);

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
                        ->action(function (Set $set): void {
                            $set('amount', '1.88');
                        }),
                    Action::make('quick_amounts_2')
                        ->label('2.88元')
                        ->action(function (Set $set): void {
                            $set('amount', '2.88');
                        }),
                    Action::make('quick_amounts_3')
                        ->label('3.88元')
                        ->action(function (Set $set): void {
                            $set('amount', '3.88');
                        }),
                ]),
        ]);

        $this->action(function (array $data, Redpack $record, RedpackService $service): void {
            $count = (int) $data['count'];
            $amount = $data['amount'];

            $created = $service->createCodesBulk($record, $count, $amount);

            $this->successNotificationTitle("已成功为该活动生成 $created 个红包码。");
            $this->success();
        });
    }
}
