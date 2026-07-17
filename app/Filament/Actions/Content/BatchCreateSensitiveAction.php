<?php

namespace App\Filament\Actions\Content;

use App\Models\System\Sensitive;
use Filament\Actions\Action;
use Filament\Forms;

class BatchCreateSensitiveAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'batchCreateSensitive';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量创建');

        $this->visible(fn (): bool => userCan(self::getDefaultName(), Sensitive::class));

        $this->schema([
            Forms\Components\Textarea::make('words')
                ->label('敏感词')
                ->rows(8)
                ->helperText('每行一个词，如果有重复的，会自动过滤')
                ->required(),
        ]);

        $this->action(function (array $data): void {
            $list = explode("\n", $data['words']);
            $list = array_unique($list);

            foreach ($list as $word) {
                Sensitive::create(['keywords' => $word]);
            }

            $this->successNotificationTitle('操作成功');
            $this->success();
        });
    }
}
