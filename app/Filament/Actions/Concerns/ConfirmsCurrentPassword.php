<?php

namespace App\Filament\Actions\Concerns;

use Filament\Forms\Components\TextInput;

/**
 * 敏感操作前要求输入当前密码确认，防止误操作。
 *
 * 通过 Filament 内置的 currentPassword() 规则校验当前登录用户的密码；
 * dehydrated(false) 保证该字段不进入表单提交数据。
 */
trait ConfirmsCurrentPassword
{
    /**
     * 当前密码确认字段
     */
    protected function getCurrentPasswordField(): TextInput
    {
        return TextInput::make('password')
            ->label('操作密码')
            ->required()
            ->password()
            ->dehydrated(false)
            ->currentPassword();
    }
}
