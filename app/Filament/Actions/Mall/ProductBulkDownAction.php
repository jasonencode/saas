<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\ProductStatus;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class ProductBulkDownAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'bulkDown';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量下架');
        $this->icon('heroicon-o-arrow-down-circle');
        $this->color('danger');
        $this->requiresConfirmation();
        $this->action(fn (Collection $records) => $this->execute($records));
        $this->deselectRecordsAfterCompletion();
    }

    /**
     * 执行批量下架
     */
    public function execute(Collection $records): void
    {
        $records->each(fn ($record) => $record->update(['status' => ProductStatus::Down]));

        Notification::make()
            ->title('批量下架成功')
            ->success()
            ->send();
    }
}
