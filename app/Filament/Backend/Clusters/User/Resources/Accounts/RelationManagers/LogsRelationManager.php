<?php

namespace App\Filament\Backend\Clusters\User\Resources\Accounts\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $modelLabel = '账变日志';

    protected static ?string $title = '账变日志';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ]);
    }
}