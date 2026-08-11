<?php

namespace App\Filament\Actions\Mall;

use App\Models\Mall\ReturnAddress;
use Filament\Actions\BulkAction;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Collection;

class ProductBulkReturnAddressAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'productBulkReturnAddress';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量修改退货地址');
        $this->icon(Heroicon::OutlinedMapPin);
        $this->color('primary');

        $this->visible(fn (HasTable $livewire): bool => userCan(self::getDefaultName(), $livewire->getTable()->getModel()));

        $this->requiresConfirmation();
        $this->modalHeading('批量修改退货地址');
        $this->modalSubmitActionLabel('确认修改');
        $this->deselectRecordsAfterCompletion();

        $this->schema([
            Forms\Components\Select::make('return_address_id')
                ->label('退货地址')
                ->options(fn () => ReturnAddress::ofEnabled()
                    ->orderByDesc('is_default')
                    ->bySort()
                    ->get()
                    ->mapWithKeys(fn (ReturnAddress $address) => [
                        $address->id => sprintf(
                            '%s %s %s',
                            $address->name,
                            $address->phone,
                            $address->is_default ? '（默认）' : '',
                        ),
                    ]))
                ->searchable()
                ->preload()
                ->required()
                ->placeholder('选择退货地址'),
        ]);

        $this->action(fn (Collection $records, array $data) => $this->execute($records, $data));
    }

    /**
     * 执行批量修改退货地址
     */
    public function execute(Collection $records, array $data): void
    {
        $records->each(fn ($record) => $record->update(['return_address_id' => $data['return_address_id']]));

        $this->successNotificationTitle('批量修改退货地址成功');
        $this->success();
    }
}
