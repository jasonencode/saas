<?php

namespace App\Admin\Clusters\Teams\Resources;

use App\Admin\Clusters\Teams;
use App\Admin\Clusters\Teams\Resources\TeamResource\Pages\ManageTeams;
use App\Admin\Clusters\Teams\Resources\TeamResource\Pages\ViewTeam;
use App\Admin\Clusters\Teams\Resources\TeamResource\RelationManagers\RolesRelationManager;
use App\Admin\Clusters\Teams\Resources\TeamResource\RelationManagers\StaffersRelationManager;
use App\Models\Team;
use Filament\Resources\Resource;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $modelLabel = '团队';

    protected static ?string $navigationIcon = 'heroicon-c-cpu-chip';

    protected static ?string $navigationLabel = '团队管理';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Teams::class;

    public static function getRelations(): array
    {
        return [
            StaffersRelationManager::class,
            RolesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTeams::route('/'),
            'view' => ViewTeam::route('/{record}'),
        ];
    }
}
