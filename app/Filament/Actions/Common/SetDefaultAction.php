<?php

namespace App\Filament\Actions\Common;

use App\Models\Model;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class SetDefaultAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'setDefault';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('设为默认');
        $this->icon(Heroicon::OutlinedStar);

        $this->visible(function (Model $record): bool {
            return !$record->is_default && userCan(self::getDefaultName(), $record);
        });

        $this->requiresConfirmation();

        $this->modalHeading('设为默认');
        $this->modalDescription('确定要将此记录设为默认吗？');

        $this->action(function (Model $record): void {
            $query = $record->newQuery()
                ->where('id', '!=', $record->id)
                ->where('is_default', true);

            if (method_exists($record, 'tenant')) {
                $query->where('tenant_id', $record->tenant_id);
            }

            $query->update(['is_default' => false]);

            $record->update(['is_default' => true]);

            $this->successNotificationTitle('已设为默认');
            $this->success();
        });
    }
}
