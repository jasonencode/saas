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
        $this->requiresConfirmation();
        $this->icon(Heroicon::OutlinedArrowsUpDown);
        $this->fillForm(function (Model $record) {
            return [
                'sort' => $record->sort,
            ];
        });

        $this->schema([
            Forms\Components\TextInput::make('sort')
                ->label('排序')
                ->helperText('数字越大越靠前')
                ->required()
                ->integer()
                ->autofocus(false),
        ]);

        $this->action(function (array $data, Model $record) {
            $record->sort = $data['sort'];
            $record->save();

            $this->successNotificationTitle('排序修改成功');
            $this->success();
        });
    }
}