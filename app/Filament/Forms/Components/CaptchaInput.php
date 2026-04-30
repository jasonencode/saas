<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Concerns;
use Filament\Forms\Components\Field;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Jason\Captcha\Facades\Captcha;

class CaptchaInput extends Field
{
    use HasExtraAlpineAttributes;
    use Concerns\HasExtraInputAttributes;

    public string $image = '';

    protected string $view = 'filament.forms.captcha';

    protected function setUp(): void
    {
        parent::setUp();

        $this->image = Captcha::src('admin');

        $this->rules('required|captcha')
            ->dehydrated(false)
            ->required()
            ->validationMessages([
                'required' => '验证码必须填写',
                'captcha' => '验证码不正确',
            ]);
    }
}
