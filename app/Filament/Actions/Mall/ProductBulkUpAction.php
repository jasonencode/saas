<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\ProductStatus;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Collection;

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
        $this->icon(Heroicon::OutlinedArrowUpCircle);
        $this->color('success');

        $this->visible(fn (HasTable $livewire): bool => userCan(self::getDefaultName(), $livewire->getTable()->getModel()));

        $this->requiresConfirmation();
        $this->deselectRecordsAfterCompletion();

        $this->action(fn (Collection $records) => $this->execute($records));
    }

    /**
     * 执行批量上架
     */
    public function execute(Collection $records): void
    {
        $records->each(fn ($record) => $record->update(['status' => ProductStatus::Up]));

        $this->successNotificationTitle('批量上架成功');
        $this->success();
    }
}
