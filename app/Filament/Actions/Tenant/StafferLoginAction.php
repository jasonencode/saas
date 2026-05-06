<?php

namespace App\Filament\Actions\Tenant;

use App\Models\System\Administrator;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class StafferLoginAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'stafferLogin';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('员工登录');
        $this->icon(Heroicon::OutlinedPaperAirplane);
        $this->visible(fn (Administrator $staffer): bool => userCan(self::getDefaultName(), $staffer));
        $this->url(function (Administrator $staffer): string {
            return route('filament.tenant.auth.login', [
                'username' => $staffer->username,
                'password' => '',
            ]);
        }, true);
    }
}
