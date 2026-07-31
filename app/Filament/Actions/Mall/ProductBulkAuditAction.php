<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\ProductStatus;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Collection;

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
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->color('info');

        $this->visible(fn (HasTable $livewire): bool => userCan(self::getDefaultName(), $livewire->getTable()->getModel()));

        $this->requiresConfirmation();
        $this->deselectRecordsAfterCompletion();

        $this->action(fn (Collection $records) => $this->execute($records));
    }

    /**
     * 执行批量审核
     */
    public function execute(Collection $records): void
    {
        $records->each(fn ($record) => $record->update(['status' => ProductStatus::Up]));

        $this->successNotificationTitle('批量审核成功');
        $this->success();
    }
}
