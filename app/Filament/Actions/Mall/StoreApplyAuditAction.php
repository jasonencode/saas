<?php

namespace App\Filament\Actions\Mall;

use App\Enums\ApplyStatus;
use App\Models\Mall\StoreApply;
use App\Services\StoreService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;

class StoreApplyAuditAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'storeApplyAudit';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('审核');
        $this->visible(fn (StoreApply $record) => $record->status === ApplyStatus::Pending);
        $this->modalWidth(Width::Large);
        $this->schema([
            ToggleButtons::make('status')
                ->label('审核结果')
                ->required()
                ->inline()
                ->options([
                    ApplyStatus::Approved->value => '通过',
                    ApplyStatus::Rejected->value => '拒绝',
                ])
                ->live()
                ->default(ApplyStatus::Approved->value),
            Textarea::make('reason')
                ->label(fn (Get $get) => $get('status') === ApplyStatus::Rejected->value ? '拒绝原因' : '通过备注')
                ->required()
                ->rows(4)
                ->maxLength(255),
        ]);

        $this->action(function (StoreApply $record, array $data, StoreService $service) {
            $service->auditApply($record, $data['status'], $data['reason']);

            $this->successNotificationTitle('审核成功');
            $this->success();
        });
    }
}
