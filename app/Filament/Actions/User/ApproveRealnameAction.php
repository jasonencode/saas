<?php

namespace App\Filament\Actions\User;

use App\Enums\User\RealnameStatus;
use App\Models\User\UserRealname;
use App\Services\RealnameService;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ApproveRealnameAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'approveRealname';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('通过');
        $this->icon(Heroicon::OutlinedCheckBadge);
        $this->color('success');
        $this->modalWidth(Width::Medium);
        $this->modalHeading(fn () => '确认通过实名认证');
        $this->modalDescription(fn () => '确定要通过该用户的实名认证申请吗？');
        $this->hidden(fn (UserRealname $record): bool => $record->status !== RealnameStatus::Pending);

        $this->action(function (UserRealname $record) {
            app(RealnameService::class)->approve($record);
            $this->success();
        });
    }
}
