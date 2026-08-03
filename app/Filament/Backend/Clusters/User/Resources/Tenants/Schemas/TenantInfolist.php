<?php

namespace App\Filament\Backend\Clusters\User\Resources\Tenants\Schemas;

use App\Models\System\Tenant;
use Carbon\Carbon;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class TenantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns()
            ->components([
                Schemas\Components\Fieldset::make('基础信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('名称')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('slug')
                            ->label('简称')
                            ->copyable(),
                        Infolists\Components\ImageEntry::make('avatar')
                            ->label('LOGO')
                            ->circular(),
                        Infolists\Components\IconEntry::make('status')
                            ->label(__('backend.status')),
                    ]),
                Schemas\Components\Grid::make()
                    ->columns(1)
                    ->schema([
                        Schemas\Components\Fieldset::make('可用模块')
                            ->schema([
                                Infolists\Components\TextEntry::make('modules')
                                    ->label('已启用模块')
                                    ->badge()
                                    ->color(fn (Tenant $tenant, mixed $state): ?string => $state?->getColor())
                                    ->state(fn (Tenant $tenant): array => $tenant->getModules())
                                    ->placeholder('无可用模块'),
                            ]),
                        Schemas\Components\Fieldset::make('时间信息')
                            ->schema([
                                Infolists\Components\TextEntry::make('expired_at')
                                    ->label('到期时间')
                                    ->color(fn (?Carbon $state): ?string => match (true) {
                                        $state && $state <= now() => 'danger',
                                        $state && $state <= now()->addMonth() => 'warning',
                                        default => null,
                                    }),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label(__('backend.created_at')),
                            ]),
                    ]),

            ]);
    }
}
