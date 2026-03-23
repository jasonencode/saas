<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Redpacks\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class RedpackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('活动名称')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('活动描述')
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('start_at')
                    ->label('开始时间'),
                Forms\Components\DateTimePicker::make('end_at')
                    ->label('结束时间'),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status')),
            ]);
    }
}
