<?php

namespace App\Filament\Actions\Tenant;

use App\Models\Tenant;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\Action;

class TenantRenewalAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'tenantRenewal';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->visible(fn(Tenant $tenant) => userCan('tenantRenewal', $tenant));
        $this->label('租户续期');
        $this->icon('heroicon-o-calendar-date-range');
        $this->requiresConfirmation();

        $this->fillForm(function(Tenant $tenant) {
            return [
                'expired_at' => $tenant->expired_at->addYear(),
            ];
        });
        $this->form([
            DatePicker::make('expired_at')
                ->label('到期时间')
                ->required()
                ->minDate(now())
                ->displayFormat('Y-m-d'),
        ]);

        $this->action(function(Tenant $tenant, array $data) {
            $tenant->expired_at = $data['expired_at'];
            $tenant->save();

            $this->successNotificationTitle('租户续期成功');
            $this->success();
        });
    }
}
