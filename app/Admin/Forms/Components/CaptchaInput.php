<?php

namespace App\Admin\Forms\Components;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Concerns;
use Filament\Forms\Components\Field;
use Filament\Support\Concerns\HasExtraAlpineAttributes;

class CaptchaInput extends Field
{
    use Concerns\CanBeLengthConstrained;
    use Concerns\HasAffixes;
    use Concerns\HasExtraInputAttributes;
    use HasExtraAlpineAttributes;

    protected string $view = 'forms.components.captcha';

    // 验证码过期时间（秒）
    protected int $expiration = 300;

    // 验证码类型
    protected string $type = 'default';

    public function length(int $length): static
    {
        $this->length = $length;
        $this->maxLength($length);

        return $this;
    }

    public function expiration(int $seconds): static
    {
        $this->expiration = $seconds;

        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getExpiration(): int
    {
        return $this->expiration;
    }

    public function getType(): string
    {
        return $this->type;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->maxLength($this->length);

        // 默认规则
        $this->rule('required')
            ->rule('string')
            ->rule('size:'.$this->length);

        // 添加验证码刷新按钮
        $this->suffixAction(
            Action::make('refresh')
                ->icon('heroicon-m-arrow-path')
                ->label('刷新验证码')
                ->action(function () {
                    $this->refreshCaptcha();
                })
        );
    }

    protected function refreshCaptcha(): void
    {
        $this->state('');
        $this->evaluate();
    }
}
