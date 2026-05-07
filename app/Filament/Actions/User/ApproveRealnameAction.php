<?php

namespace App\Filament\Actions\User;

use App\Enums\User\RealnameStatus;
use App\Models\User\UserRealname;
use App\Services\User\RealnameService;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ApproveRealnameAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('通过');
        $this->icon(Heroicon::OutlinedCheckBadge);
        $this->color('success');
        $this->modalWidth(Width::Medium);
        $this->modalHeading(fn () => '确认通过实名认证');
        $this->modalDescription(fn () => '确定要通过该用户的实名认证申请吗？');
        $this->visible(fn (UserRealname $record): bool => userCan(self::getDefaultName(), $record) && $record->status === RealnameStatus::Pending);

        $this->action(function (UserRealname $record): void {
            app(RealnameService::class)->approve($record);
            $this->success();
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'approveRealname';
    }
}
