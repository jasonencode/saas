<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Concerns;
use Filament\Forms\Components\Field;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Mews\Captcha\Facades\Captcha;

class CaptchaInput extends Field
{
    use HasExtraAlpineAttributes;
    use Concerns\HasExtraInputAttributes;

    public string $image = '';
    protected string $view = 'forms.components.captcha';

    public function refreshImage(): void
    {
        $this->image = Captcha::src();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->image = Captcha::src();

        $this->rules('required|captcha')
            ->dehydrated(false)
            ->required()
            ->validationMessages([
                'required' => '验证码必须填写',
                'captcha' => '验证码不正确',
            ]);
    }
}
