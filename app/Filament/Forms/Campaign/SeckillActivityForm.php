<?php

namespace App\Filament\Forms\Campaign;

use App\Enums\Campaign\SeckillActivityStatus;
use App\Models\System\Tenant;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;

class SeckillActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Section::make('基础信息')
                    ->columns()
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->label('租户')
                            ->options(fn (): array => Tenant::query()->orderBy('id')->pluck('name', 'id')->all())
                            ->visible(fn (): bool => filament()->getTenant() === null)
                            ->required(fn (): bool => filament()->getTenant() === null)
                            ->default(fn () => filament()->getTenant()?->getKey())
                            ->searchable(),
                        Forms\Components\TextInput::make('name')
                            ->label('活动名称')
                            ->required()
                            ->maxLength(64),
                        Forms\Components\Select::make('status')
                            ->label('活动状态')
                            ->options(SeckillActivityStatus::class)
                            ->default(SeckillActivityStatus::Draft)
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('活动说明')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('cover')
                            ->label('活动封面')
                            ->image()
                            ->directory('seckill')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('sort')
                            ->label('排序')
                            ->integer()
                            ->default(0),
                    ]),
            ]);
    }
}
