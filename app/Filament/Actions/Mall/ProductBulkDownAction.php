<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\ProductStatus;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Collection;

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
        $this->icon(Heroicon::OutlinedArrowDownCircle);
        $this->color('danger');

        $this->visible(fn (HasTable $livewire): bool => userCan(self::getDefaultName(), $livewire->getTable()->getModel()));

        $this->requiresConfirmation();
        $this->deselectRecordsAfterCompletion();

        $this->action(fn (Collection $records) => $this->execute($records));
    }

    /**
     * 执行批量下架
     */
    public function execute(Collection $records): void
    {
        $records->each(fn ($record) => $record->update(['status' => ProductStatus::Down]));

        $this->successNotificationTitle('批量下架成功');
        $this->success();
    }
}
