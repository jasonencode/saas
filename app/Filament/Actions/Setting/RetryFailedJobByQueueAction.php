<?php

namespace App\Filament\Actions\Setting;

use App\Models\System\FailedJob;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Artisan;

class RetryFailedJobByQueueAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'retryQueue';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('重试指定队列');
        $this->icon(Heroicon::OutlinedReceiptRefund);
        $this->visible(fn (): bool => userCan(self::getDefaultName(), FailedJob::class));
        $this->modalWidth(Width::Medium);
        $this->schema(function (): array {
            return [
                Forms\Components\Select::make('name')
                    ->label('队列名')
                    ->required()
                    ->options(fn () => FailedJob::select('queue')->distinct()->pluck('queue', 'queue')),
            ];
        });
        $this->action(function (array $data): void {
            Artisan::call('queue:retry --queue=' . $data['name']);
            $this->successNotificationTitle('操作成功');
            $this->success();
        });
    }
}
