<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Forms\Components\CustomUpload;
use Filament\Forms;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use function Filament\authorize;

class TenantProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return '门店资料';
    }

    public static function canView(Model $tenant): bool
    {
        try {
            return authorize('edit', $tenant)->allowed();
        } catch (AuthorizationException $exception) {
            return $exception->toResponse()->allowed();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Fieldset::make('基础信息')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('门店名称')
                            ->live(onBlur: true)
                            ->required(),
                        CustomUpload::make('avatar')
                            ->label('门店LOGO')
                            ->required()
                            ->avatar()
                            ->imageEditor()
                            ->imageResizeTargetWidth(500)
                            ->imageResizeTargetHeight(500),
                    ]),
                Fieldset::make('扩展信息')
                    ->schema([
                        Forms\Components\TextInput::make('config.open_time')
                            ->label('营业时间')
                            ->required()
                            ->helperText('格式：09:00-21:00'),
                        Forms\Components\TextInput::make('config.address')
                            ->required()
                            ->label('门店地址'),
                        Forms\Components\TextInput::make('config.location')
                            ->required()
                            ->label('门店坐标')
                            ->helperText(new HtmlString('格式：纬度,经度。例如：45.78,126.62。<a style="color: red" href="https://lbs.qq.com/getPoint/" target="_blank">坐标拾取器</a>')),
                        Forms\Components\TextInput::make('config.phone')
                            ->required()
                            ->label('联系电话'),
                    ]),
            ]);
    }
}
