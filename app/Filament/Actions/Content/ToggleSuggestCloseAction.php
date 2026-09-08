<?php

namespace App\Filament\Actions\Content;

use App\Enums\Content\SuggestStatus;
use App\Models\Content\Suggest;
use App\Services\Content\SuggestService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class ToggleSuggestCloseAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'toggleSuggestClose';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (Suggest $record): string => $this->isClosed($record) ? '重新开启' : '关闭反馈');
        $this->icon(fn (Suggest $record): Heroicon => $this->isClosed($record) ? Heroicon::OutlinedLockOpen : Heroicon::OutlinedLockClosed);
        $this->color(fn (Suggest $record): string => $this->isClosed($record) ? 'success' : 'danger');

        $this->visible(fn (Suggest $record): bool => userCan(self::getDefaultName(), $record));

        $this->requiresConfirmation();
        $this->modalHeading(fn (Suggest $record): string => $this->isClosed($record) ? '重新开启反馈' : '关闭反馈');
        $this->modalDescription(fn (Suggest $record): string => $this->isClosed($record)
            ? '确定要重新开启该反馈吗？开启后用户可以继续回复。'
            : '确定要关闭该反馈吗？关闭后用户将无法继续回复。');
        $this->modalSubmitActionLabel(fn (Suggest $record): string => $this->isClosed($record) ? '确认开启' : '确认关闭');

        $this->action(function (Suggest $record): void {
            $wasClosed = $this->isClosed($record);

            if ($wasClosed) {
                service(SuggestService::class)->reopen($record);
            } else {
                service(SuggestService::class)->close($record);
            }

            $this->successNotificationTitle($wasClosed ? '反馈已重新开启' : '反馈已关闭');
            $this->success();
        });
    }

    protected function isClosed(Suggest $record): bool
    {
        return $record->status === SuggestStatus::Closed;
    }
}
