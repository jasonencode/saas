<?php

namespace App\Filament\Actions\Setting;

use App\Models\Activity;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;

class AuditActivityAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'auditActivity';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('审计');
        $this->visible(fn(Activity $activity) => userCan(self::getDefaultName(), $activity));
        $this->hidden(fn(Activity $activity) => $activity->is_audit);

        $this->requiresConfirmation();

        $this->action(function(Activity $activity) {
            $activity->is_audit = true;
            $activity->auditor_id = Auth::id();
            $activity->save();

            $this->successNotificationTitle('审计成功');
            $this->success();
        });
    }
}
