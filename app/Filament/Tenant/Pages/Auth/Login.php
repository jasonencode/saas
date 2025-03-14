<?php

namespace App\Filament\Tenant\Pages\Auth;

use App\Filament\Forms\Components\CaptchaInput;
use Coderflex\FilamentTurnstile\Forms\Components\Turnstile;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BasePage;
use Illuminate\Validation\ValidationException;

class Login extends BasePage
{
    use HasCustomLayout;

    public function mount(): void
    {
        parent::mount();

        if (config('app.debug')) {
            $this->form->fill([
                'username' => 'dhw_admin',
                'password' => '@Aa123456',
                'remember' => true,
            ]);
        }
    }

    public function getTurnstile()
    {
        return Turnstile::make('captcha')
            ->label('身份验证')
            ->theme('light')
            ->language(config('app.locale'))
            ->size('normal');
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
//                        $this->getCaptchaFormComponent(),
                        $this->getRememberFormComponent(),
//                        $this->getTurnstile(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('用户名')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getCaptchaFormComponent()
    {
        return CaptchaInput::make('captcha')
            ->label('验证码')
            ->required()
            ->extraInputAttributes(['tabindex' => 3]);
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => __('filament-panels::pages/auth/login.messages.failed'),
            'data.captcha' => '请验证您的身份',
        ]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }
}
