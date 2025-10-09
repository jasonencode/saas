<?php

namespace App\Filament\Tenant\Pages\Auth;

use Filament\Pages\Tenancy\EditTenantProfile;

class TenantProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return '团队资料';
    }
}
