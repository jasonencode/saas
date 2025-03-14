<?php

namespace App\Filament\Actions;

use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class DisableBulkAction extends BulkAction
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'disable_bulk';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->label('批量禁用');
        $this->icon('heroicon-o-moon');
        $this->requiresConfirmation();
        $this->successNotificationTitle('已禁用选中项目');
        $this->deselectRecordsAfterCompletion();

        $this->action(function(): void {
            $this->process(static fn(Collection $records) => $records->each(fn(Model $record) => $record->disable()));

            $this->success();
        });

        $this->visible(fn(HasTable $livewire) => userCan('disableAny', $livewire->getTable()->getModel()));

        $this->hidden(function(HasTable $livewire): bool {
            $trashedFilterState = $livewire->getTableFilterState(TrashedFilter::class) ?? [];
            if (!array_key_exists('value', $trashedFilterState)) {
                return false;
            }
            if ($trashedFilterState['value']) {
                return false;
            }

            return filled($trashedFilterState['value']);
        });
    }
}
