<?php

namespace App\Filament\Actions\Common;

use App\Models\Model;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Collection;

class EnableBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'enableBulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量启用');
        $this->icon(Heroicon::OutlinedSun);

        $this->visible(fn (HasTable $livewire): bool => userCan(self::getDefaultName(), $livewire->getTable()->getModel()));

        $this->hidden(function (HasTable $livewire): bool {
            $trashedFilterState = $livewire->getTableFilterState(TrashedFilter::class) ?? [];
            if (!array_key_exists('value', $trashedFilterState)) {
                return false;
            }
            if ($trashedFilterState['value']) {
                return false;
            }

            return filled($trashedFilterState['value']);
        });

        $this->requiresConfirmation();

        $this->deselectRecordsAfterCompletion();

        $this->action(function (Collection $records): void {
            $records->each(fn (Model $record) => $record->enable());

            $this->successNotificationTitle('已启用选中项目');
            $this->success();
        });
    }
}
