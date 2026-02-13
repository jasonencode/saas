<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\Schemas;

use App\Filament\Forms\Components\TenantSelect;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RedpackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantSelect::make(),
                TextInput::make('name')
                    ->label('活动名称')
                    ->required(),
                Textarea::make('description')
                    ->label('活动描述')
                    ->rows(4)
                    ->columnSpanFull(),
                DateTimePicker::make('start_at'),
                DateTimePicker::make('end_at'),
                Toggle::make('status')
                    ->label('状态')
                    ->default(true),
            ]);
    }
}
