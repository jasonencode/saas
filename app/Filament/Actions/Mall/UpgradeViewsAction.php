<?php

namespace App\Filament\Actions\Mall;

use App\Models\Product;
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
        $this->requiresConfirmation();
        $this->icon(Heroicon::OutlinedEye);

        $this->fillForm(function (Product $record) {
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

        $this->action(function (array $data, Product $record) {
            $record->update(['views' => $data['views']]);

            $this->successNotificationTitle('流量量修改成功');
            $this->success();
        });

//        Action::make('views')
//            ->requiresConfirmation()
//            ->modalHeading('修改浏览量')
//            ->fillForm(function (Product $record) {
//                return ['views' => $record->views];
//            })
//            ->schema([
//                TextInput::make('views')
//                    ->label('浏览量')
//                    ->required()
//                    ->integer()
//                    ->autofocus(false),
//            ])
//            ->action(function (array $data, Product $record, Action $action) {
//                $record->views = $data['views'];
//                $record->save();
//                $action->successNotificationTitle('操作成功');
//                $action->success();
//            })
    }
}