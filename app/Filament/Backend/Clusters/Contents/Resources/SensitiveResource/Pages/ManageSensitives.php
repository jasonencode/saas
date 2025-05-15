<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\SensitiveResource\Pages;

use App\Filament\Backend\Clusters\Contents\Resources\SensitiveResource;
use App\Models\Sensitive;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ManageRecords;

class ManageSensitives extends ManageRecords
{
    protected static string $resource = SensitiveResource::class;

    protected function getActions(): array
    {
        return [
            Actions\Action::make('batchCreate')
                ->label('批量创建')
                ->visible(fn(): bool => userCan('create', self::$resource::getModel()))
                ->form([
                    Forms\Components\Textarea::make('words')
                        ->label('敏感词')
                        ->rows(8)
                        ->helperText('每行一个词，如果有重复的，会自动过滤')
                        ->required(),
                ])
                ->action(function(array $data, Actions\Action $action) {
                    $list = explode("\n", $data['words']);
                    $list = array_unique($list);

                    foreach ($list as $word) {
                        Sensitive::create(['keywords' => $word]);
                    }
                    $action->successNotificationTitle('操作成功');
                    $action->success();
                }),
        ];
    }
}
