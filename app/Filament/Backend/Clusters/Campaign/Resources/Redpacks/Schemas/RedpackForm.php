<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\Schemas;

use App\Filament\Forms\Components\TenantSelect;
use Filament\Forms;
use Filament\Schemas\Schema;

class RedpackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantSelect::make(),
                Forms\Components\TextInput::make('name')
                    ->label('活动名称')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('活动描述')
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('start_at'),
                Forms\Components\DateTimePicker::make('end_at'),
                Forms\Components\Toggle::make('status')
                    ->label('状态'),
            ]);
    }
}
