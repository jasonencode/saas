<?php

namespace App\Filament\Backend\Pages;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class Profile extends EditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
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
