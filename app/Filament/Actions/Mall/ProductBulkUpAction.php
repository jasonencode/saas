<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\ProductStatus;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class ProductBulkUpAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'bulkUp';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量上架');
        $this->icon('heroicon-o-arrow-up-circle');
        $this->color('success');
        $this->requiresConfirmation();
        $this->action(fn (Collection $records) => $this->execute($records));
        $this->deselectRecordsAfterCompletion();
    }

    /**
     * 执行批量上架
     */
    public function execute(Collection $records): void
    {
        $records->each(fn ($record) => $record->update(['status' => ProductStatus::Up]));

        Notification::make()
            ->title('批量上架成功')
            ->success()
            ->send();
    }
}
