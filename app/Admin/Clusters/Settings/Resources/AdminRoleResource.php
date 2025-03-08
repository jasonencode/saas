<?php

namespace App\Admin\Clusters\Settings\Resources;

use App\Admin\Clusters\Settings;
use App\Admin\Clusters\Settings\Resources\AdminRoleResource\Pages;
use App\Admin\Clusters\Settings\Resources\AdminRoleResource\RelationManagers;
use App\Models\AdminRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

class AdminRoleResource extends Resource
{
    protected static ?string $model = AdminRole::class;

    protected static ?string $modelLabel = '角色';

    protected static ?string $navigationIcon = 'heroicon-c-user-circle';

    protected static ?string $navigationLabel = '角色管理';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Settings::class;

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

    public static function getRelations(): array
    {
        return [
            RelationManagers\StaffersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRoles::route('/'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
