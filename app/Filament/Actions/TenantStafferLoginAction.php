<?php

namespace App\Filament\Actions;

use App\Models\Administrator;
use Filament\Tables\Actions\Action;

class TenantStafferLoginAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'tenant_staffer_login';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('租户登录');

        $this->url(function(Administrator $staffer) {
            return route('filament.tenant.auth.login', [
                'username' => $staffer->username,
                'password' => '@Aa123456',
            ]);
        }, true);
    }
}
