<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Express;
use App\Models\Mall\Refund;
use App\Services\Mall\RefundService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ShipReturnAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'shipReturn';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('提交退货物流');
        $this->icon(Heroicon::OutlinedTruck);
        $this->color('info');
        $this->visible(fn (Refund $refund): bool => userCan(self::getDefaultName(), $refund) && $refund->status === RefundStatus::WaitingReturn);
        $this->requiresConfirmation();
        $this->modalHeading('提交退货物流');
        $this->modalDescription('请填写退货物流信息');
        $this->schema([
            Select::make('express_id')
                ->label('快递公司')
                ->options(Express::query()->pluck('name', 'id'))
                ->required()
                ->searchable(),
            TextInput::make('express_no')
                ->label('物流单号')
                ->required()
                ->maxLength(32),
        ]);
        $this->action(function (Refund $refund, array $data): void {
            try {
                service(RefundService::class)
                    ->shipReturn($refund, Filament::auth()->user(), $data);

                $this->successNotificationTitle('物流信息已提交');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}
