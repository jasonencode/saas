<?php

namespace App\Filament\Actions\Common;

use App\Models\Administrator;
use Filament\Tables\Actions\Action;

class TenantStafferLoginAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'tenantStafferLogin';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('租户登录');
        $this->visible(fn(Administrator $staffer) => userCan('tenantStafferLogin', $staffer));
        $this->url(function(Administrator $staffer) {
            return route('filament.tenant.auth.login', [
                'username' => $staffer->username,
                'password' => '@Aa123456',
            ]);
        }, true);
    }
}
