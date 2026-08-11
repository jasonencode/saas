<?php

namespace App\Filament\Actions\Common;

use App\Models\Model;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;

class UpgradeViewsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'upgradeViews';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('修改浏览量');
        $this->icon(Heroicon::OutlinedEye);

        $this->visible(fn (Model $record): bool => userCan(self::getDefaultName(), $record));

        $this->requiresConfirmation();

        $this->fillForm(function (Model $record): array {
            return [
                'views' => $record->views,
            ];
        });
        $this->schema([
            Forms\Components\TextInput::make('views')
                ->label('浏览量')
                ->required()
                ->integer()
                ->autofocus(false),
        ]);

        $this->action(function (Model $record, array $data): void {
            $record->views = $data['views'];
            $record->save();

            $this->successNotificationTitle('浏览量修改成功');
            $this->success();
        });
    }
}
