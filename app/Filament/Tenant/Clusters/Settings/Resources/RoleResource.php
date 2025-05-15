<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources;

use App\Filament\Tenant\Clusters\Settings;
use App\Filament\Tenant\Clusters\Settings\Resources\RoleResource\Pages;
use App\Models\AdminRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

class RoleResource extends Resource
{
    protected static ?string $model = AdminRole::class;

    protected static ?string $modelLabel = '角色';

    protected static ?string $navigationLabel = '角色管理';

    protected static ?string $navigationIcon = 'heroicon-c-user-circle';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('角色名称')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('描述')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRoles::route('/'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
