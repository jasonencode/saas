<?php

namespace App\Filament\Tenant\Pages\Auth;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getAvatarFormComponent(),
            ]);
    }

    private function getAvatarFormComponent(): FileUpload
    {
        return CustomUpload::make('avatar')
            ->label('头像')
            ->avatar()
            ->imageEditor()
            ->imageResizeTargetWidth(200)
            ->imageResizeTargetHeight(200);
    }
}
