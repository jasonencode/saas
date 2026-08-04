<?php

namespace App\Filament\Tenant\Pages;

use App\Models\System\Tenant;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;

/**
 * 租户已过期页面
 *
 * 当租户的 expired_at 已过期时，EnsureTenantNotExpired 中间件会把
 * 租户面板的所有请求重定向到本页面。本页面不注册导航、不可操作，
 * 仅展示过期状态与续期提示。
 */
class TenantExpired extends Page
{
    protected ?string $heading = '租户已过期';

    /**
     * 不注册到侧边导航。
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /**
     * 仅在租户已过期时可访问。
     */
    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Tenant && $tenant->isExpired();
    }

    /**
     * 渲染过期提示卡片，包含到期时间与续期引导。
     */
    public function content(Schema $schema): Schema
    {
        /** @var Tenant $tenant */
        $tenant = Filament::getTenant();

        return $schema->components([
            Section::make()
                ->heading(__('租户已过期'))
                ->description(__('当前租户已超过到期时间，所有操作均被暂停。请联系平台管理员续期。'))
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->iconColor('danger')
                ->iconSize(IconSize::TwoExtraLarge)
                ->columns()
                ->schema([
                    TextEntry::make('name')
                        ->label('租户名称')
                        ->state($tenant->name),
                    TextEntry::make('expired_at')
                        ->label('到期时间')
                        ->state($tenant->expired_at)
                        ->color('danger'),
                ]),
        ]);
    }
}
