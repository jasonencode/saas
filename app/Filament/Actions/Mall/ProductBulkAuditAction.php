<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\ProductStatus;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class ProductBulkAuditAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'bulkAudit';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量审核');
        $this->icon('heroicon-o-check-circle');
        $this->color('info');
        $this->requiresConfirmation();
        $this->action(fn (Collection $records) => $this->execute($records));
        $this->deselectRecordsAfterCompletion();
    }

    /**
     * 执行批量审核
     */
    public function execute(Collection $records): void
    {
        $records->each(fn ($record) => $record->update(['status' => ProductStatus::Up]));

        Notification::make()
            ->title('批量审核成功')
            ->success()
            ->send();
    }
}
