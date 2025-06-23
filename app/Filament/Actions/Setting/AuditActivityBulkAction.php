<?php

namespace App\Filament\Actions\Setting;

use App\Models\Activity;
use Filament\Forms\Components\Radio;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class AuditActivityBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'auditActivityBulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量审计');
        $this->visible(fn(Activity $activity) => userCan(self::getDefaultName(), $activity));

        $this->requiresConfirmation();

        $this->form([
            Radio::make('result')
                ->label('审计结果')
                ->required()
                ->inline()
                ->inlineLabel(false)
                ->default(true)
                ->boolean(),
        ]);

        $this->action(function(Collection $records, array $data) {
            $records->each(function(Activity $activity) use ($data) {
                $activity->is_audit = $data['result'];
                $activity->auditor_id = Auth::id();
                $activity->save();
            });

            $this->successNotificationTitle('批量审计成功');
            $this->success();
        });
    }
}
