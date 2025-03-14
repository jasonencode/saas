<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources;

use App\Filament\Tenant\Clusters\Settings;
use App\Filament\Tenant\Clusters\Settings\Resources\RoleResource\Pages\EditRole;
use App\Filament\Tenant\Clusters\Settings\Resources\RoleResource\Pages\ManageRoles;
use App\Models\Role;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $modelLabel = '角色';

    protected static ?string $navigationLabel = '角色管理';

    protected static ?string $navigationIcon = 'heroicon-c-user-circle';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('角色名称')
                    ->required(),
                Textarea::make('description')
                    ->label('描述')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRoles::route('/'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
