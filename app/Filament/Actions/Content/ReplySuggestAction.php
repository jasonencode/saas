<?php

namespace App\Filament\Actions\Content;

use App\Enums\Content\SuggestStatus;
use App\Models\Content\Suggest;
use App\Services\Content\SuggestService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ReplySuggestAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'replySuggest';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('回复');
        $this->icon(Heroicon::OutlinedPaperAirplane);
        $this->color('primary');

        $this->visible(function (): bool {
            /** @var Suggest $record */
            $record = $this->getLivewire()->getOwnerRecord();

            return userCan(self::getDefaultName(), $record) && $record->status !== SuggestStatus::Closed;
        });

        $this->modalWidth(Width::Medium);
        $this->modalHeading('回复反馈');
        $this->modalDescription('回复后将自动把反馈状态改为已回复。');
        $this->modalSubmitActionLabel('发送回复');

        $this->schema([
            Forms\Components\Textarea::make('content')
                ->label('回复内容')
                ->required()
                ->maxLength(500)
                ->rows(4),
        ]);

        $this->action(function (array $data): void {
            /** @var Suggest $record */
            $record = $this->getLivewire()->getOwnerRecord();

            service(SuggestService::class)->reply($record, Filament::auth()->user(), $data['content']);

            $this->successNotificationTitle('回复成功');
            $this->success();
        });
    }
}
