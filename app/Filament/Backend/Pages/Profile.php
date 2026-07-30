<?php

namespace App\Filament\Backend\Pages;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class Profile extends EditProfile
{
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::ExtraLarge;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基础资料')
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getCurrentPasswordFormComponent(),
                        $this->getAvatarFormComponent(),
                    ]),
            ]);
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::Center;
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
}
