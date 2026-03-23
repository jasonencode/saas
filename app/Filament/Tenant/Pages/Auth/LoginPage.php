<?php

namespace App\Filament\Tenant\Pages\Auth;

use App\Filament\Forms\Components\CaptchaInput;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Auth\Pages\Login;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class LoginPage extends Login
{
    use HasCustomLayout;

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'username' => request()->get('username'),
            'password' => request()->get('password'),
            'remember' => true,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getCaptchaFormComponent(),
                $this->getRememberFormComponent(),
            ]);
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

    protected function getCaptchaFormComponent(): CaptchaInput
    {
        return CaptchaInput::make('captcha')
            ->label('验证码')
            ->required()
            ->extraInputAttributes(['tabindex' => 3]);
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => __('filament-panels::auth/pages/login.messages.failed'),
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
