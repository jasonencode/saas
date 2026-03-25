<?php

namespace App\Filament\Actions\Tenant;

use App\Models\Tenant;
use App\Services\TenantService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;

class RenewalAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'tenantRenewal';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->visible(fn (Tenant $tenant) => userCan(self::getDefaultName(), $tenant));
        $this->label('租户续期');
        $this->icon(Heroicon::OutlinedCalendarDateRange);
        $this->requiresConfirmation();

        $this->fillForm(function (Tenant $tenant) {
            return [
                'expired_at' => $tenant->expired_at->addYear(),
            ];
        });
        $this->schema([
            DatePicker::make('expired_at')
                ->label('到期时间')
                ->required()
                ->minDate(now())
                ->displayFormat('Y-m-d'),
        ]);

        $this->action(function (Tenant $tenant, array $data) {
            resolve(TenantService::class)->renew($tenant, $data['expired_at']);
            $this->successNotificationTitle('租户续期成功');
            $this->success();
        });
    }
}
