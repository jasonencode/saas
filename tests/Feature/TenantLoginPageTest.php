<?php

namespace Tests\Feature;

use App\Filament\Tenant\Pages\Auth\LoginPage;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 租户登录页入参口径
 */
class TenantLoginPageTest extends TestCase
{
    public function test_login_page_only_prefills_username_from_url(): void
    {
        Filament::setCurrentPanel('tenant');

        Livewire::withQueryParams([
            'username' => 'staffer-01',
            'password' => 'leaked-password',
        ])
            ->test(LoginPage::class)
            ->assertSet('data.username', 'staffer-01')
            ->assertSet('data.password', null)
            ->assertSet('data.remember', false);
    }
}
