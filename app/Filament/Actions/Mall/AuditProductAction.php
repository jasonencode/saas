<?php

namespace App\Filament\Actions\Mall;

use App\Enums\ProductStatus;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class AuditProductAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'audit';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('审核');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->color('info');
        $this->visible(fn(Product $record) => $record->status === ProductStatus::Pending);
        $this->modalWidth(Width::Large);
        $this->schema([
            Forms\Components\Radio::make('status')
                ->label('审核结果')
                ->options([
                    ProductStatus::Up->value => '通过',
                    ProductStatus::Rejected->value => '驳回',
                ])
                ->default(ProductStatus::Up->value)
                ->required()
                ->live(),
            Forms\Components\Textarea::make('reason')
                ->label('驳回原因')
                ->rows(3)
                ->required()
                ->visible(fn(Get $get) => $get('status') === ProductStatus::Rejected->value),
        ]);
        $this->action(function (Product $record, array $data) {
            $record->update(['status' => $data['status']]);

            $this->successNotificationTitle('审核完成');
            $this->success();
        });
    }
}
