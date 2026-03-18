<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class Profile extends EditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
                $this->getAvatarFormComponent(),
            ]);
    }

    private function getAvatarFormComponent(): FileUpload
    {
        return CustomUpload::make('avatar')
            ->label('头像')
            ->avatar()
            ->imageEditor()
            ->automaticallyResizeImagesToWidth(200)
            ->automaticallyResizeImagesToHeight(200);
    }

    public function getRedirectUrl(): ?string
    {
        return route('filament.tenant.tenant');
    }
}
