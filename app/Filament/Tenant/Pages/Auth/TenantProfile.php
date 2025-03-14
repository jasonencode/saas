<?php

namespace App\Filament\Tenant\Pages\Auth;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;

class TenantProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return '团队资料';
    }

    public function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                TextInput::make('name')
                    ->label('团队名称')
                    ->required(),
                $this->getAvatarFormComponent(),
            ]);
    }

    private function getAvatarFormComponent()
    {
        return CustomUpload::make('avatar')
            ->label('LOGO')
            ->avatar()
            ->imageEditor()
            ->imageResizeTargetWidth(200)
            ->imageResizeTargetHeight(200);
    }
}
