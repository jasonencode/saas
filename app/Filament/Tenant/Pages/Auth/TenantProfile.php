<?php

namespace App\Filament\Tenant\Pages\Auth;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;
use Illuminate\Support\Js;

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
                    ->label('租户名称')
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

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Action::make('back')
                ->label(__('filament-panels::pages/auth/edit-profile.actions.cancel.label'))
                ->alpineClickHandler('document.referrer ? window.history.back() : (window.location.href = '.Js::from(filament()->getUrl()).')')
                ->color('gray'),
        ];
    }

}
