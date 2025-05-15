<?php

namespace App\Filament\Backend\Pages\Auth;

use App\Filament\Forms\Components\CaptchaInput;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Pages\Auth\Login as BasePage;
use Illuminate\Validation\ValidationException;

class Login extends BasePage
{
    use HasCustomLayout;

    public function mount(): void
    {
        parent::mount();

        if (config('app.debug') && config('app.env') == 'local') {
            $this->form->fill([
                'username' => 'jason',
                'password' => '123123',
                'test' => true,
                'remember' => true,
            ]);
        }
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getCaptchaFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getEmailFormComponent(): Component
    {
        return Forms\Components\TextInput::make('username')
            ->label('用户名')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getCaptchaFormComponent(): Component
    {
        if (config('app.debug')) {
            return Forms\Components\Toggle::make('test')
                ->default(true);
        }
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
