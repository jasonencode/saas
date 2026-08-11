<?php

namespace App\Filament\Actions\Campaign;

use App\Models\Campaign\Redpack;
use App\Models\Campaign\RedpackCode;
use App\Services\Campaign\RedpackService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

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
        $this->icon(Heroicon::OutlinedPlusCircle);

        $this->modalWidth(Width::Medium);

        $this->schema([
            Forms\Components\TextInput::make('count')
                ->label('生成数量')
                ->integer()
                ->required()
                ->minValue(1)
                ->maxValue(50000)
                ->default(100),
            Forms\Components\TextInput::make('length')
                ->label('码长度')
                ->integer()
                ->default(RedpackCode::CODE_LENGTH_DEFAULT)
                ->minValue(RedpackCode::CODE_LENGTH_MIN)
                ->maxValue(RedpackCode::CODE_LENGTH_MAX)
                ->helperText('请输入码长度，6-16位之间。')
                ->required(),
            Forms\Components\Radio::make('type')
                ->label('金额类型')
                ->options([
                    'fixed' => '固定金额',
                    'random' => '随机金额',
                ])
                ->default('fixed')
                ->live()
                ->reactive(),
            Forms\Components\TextInput::make('amount')
                ->label('单个金额')
                ->numeric()
                ->required()
                ->minValue(0.3)
                ->suffix('元')
                ->visible(fn (Get $get): bool => $get('type') === 'fixed')
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
            Schemas\Components\Grid::make(2)
                ->visible(fn (Get $get): bool => $get('type') === 'random')
                ->schema([
                    Forms\Components\TextInput::make('min_amount')
                        ->label('最小金额')
                        ->numeric()
                        ->required()
                        ->minValue(0.3)
                        ->default(0.3)
                        ->suffix('元'),
                    Forms\Components\TextInput::make('max_amount')
                        ->label('最大金额')
                        ->numeric()
                        ->required()
                        ->minValue(0.3)
                        ->suffix('元'),
                ]),
        ]);

        $this->action(function (array $data, RedpackService $service): void {
            /** @var Redpack $record */
            $record = $this->getLivewire()->getOwnerRecord();

            $count = (int) $data['count'];
            $type = $data['type'];
            $length = (int) $data['length'];

            $created = match ($type) {
                'fixed' => $service->createCodesBulk(
                    redpack: $record,
                    count: $count,
                    amount: (float) $data['amount'],
                    type: $type,
                    codeLength: $length,
                ),
                'random' => $service->createCodesBulk(
                    redpack: $record,
                    count: $count,
                    amount: (float) $data['min_amount'],
                    type: $type,
                    codeLength: $length,
                    maxAmount: (float) $data['max_amount'],
                ),
            };

            $this->successNotificationTitle("已成功为该活动生成 $created 个红包码。");
            $this->success();
        });
    }
}
