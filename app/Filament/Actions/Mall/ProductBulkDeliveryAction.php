<?php

namespace App\Filament\Actions\Mall;

use App\Models\Mall\Delivery;
use Filament\Actions\BulkAction;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Collection;

class ProductBulkDeliveryAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'productBulkDelivery';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量修改运费模板');
        $this->icon(Heroicon::OutlinedTruck);
        $this->color('primary');

        $this->visible(fn (HasTable $livewire): bool => userCan(self::getDefaultName(), $livewire->getTable()->getModel()));

        $this->requiresConfirmation();
        $this->modalHeading('批量修改运费模板');
        $this->modalSubmitActionLabel('确认修改');
        $this->deselectRecordsAfterCompletion();

        $this->schema([
            Forms\Components\Select::make('delivery_id')
                ->label('运费模板')
                ->options(fn () => Delivery::pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required()
                ->placeholder('选择运费模板'),
        ]);

        $this->action(fn (Collection $records, array $data) => $this->execute($records, $data));
    }

    /**
     * 执行批量修改运费模板
     */
    public function execute(Collection $records, array $data): void
    {
        $records->each(fn ($record) => $record->update(['delivery_id' => $data['delivery_id']]));

        $this->successNotificationTitle('批量修改运费模板成功');
        $this->success();
    }
}
