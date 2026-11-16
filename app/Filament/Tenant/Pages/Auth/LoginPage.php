<?php

namespace App\Filament\Tenant\Pages\Auth;

use App\Filament\Forms\Components\CaptchaInput;
use App\Filament\Pages\Concerns\RequiresCaptchaAfterFailure;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\Concerns\HasCustomLayout;
use Filament\Auth\Pages\Login;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class LoginPage extends Login
{
    use HasCustomLayout;
    use RequiresCaptchaAfterFailure;

    public function mount(): void
    {
        parent::mount();

        // 只接受用户名预填；密码绝不从 URL 读取，避免钓鱼链接/浏览器历史泄露
        $this->form->fill([
            'username' => request()->input('username'),
            'remember' => false,
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
            ->visible(fn (): bool => $this->isCaptchaRequired())
            ->extraInputAttributes(['tabindex' => 3]);
    }

    protected function throwFailureValidationException(): never
    {
        $this->markCaptchaRequired();

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
