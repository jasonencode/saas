<?php

namespace App\Filament\Actions\Common;

use App\Models\Model;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;

class UpgradeSortAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'upgradeSort';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('修改排序');
        $this->icon(Heroicon::OutlinedArrowsUpDown);

        $this->visible(fn (Model $record): bool => userCan(self::getDefaultName(), $record));

        $this->requiresConfirmation();

        $this->fillForm(function (Model $record): array {
            return [
                'sort' => $record->sort ?? 0,
            ];
        });
        $this->schema([
            Forms\Components\TextInput::make('sort')
                ->label(__('backend.sort'))
                ->helperText('数字越大越靠前')
                ->required()
                ->integer()
                ->autofocus(false),
        ]);

        $this->action(function (Model $record, array $data): void {
            $record->sort = $data['sort'];
            $record->save();

            $this->successNotificationTitle('排序修改成功');
            $this->success();
        });
    }
}
