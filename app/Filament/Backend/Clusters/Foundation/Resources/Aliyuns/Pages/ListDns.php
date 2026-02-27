<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\Pages;

use App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\AliyunResource;
use App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\RelationManagers\DnsRelationManager;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Session;

class ListDns extends ViewRecord
{
    protected static string $resource = AliyunResource::class;

    public ?string $domain = null;

    public function mount(string|int $record): void
    {
        parent::mount($record);
        $this->domain = request()->route('domain');
        if ($this->domain) {
            Session::put('filament_aliyun_dns_domain', $this->domain);
        }
    }

    public function getTitle(): string|Htmlable
    {
        return $this->domain;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextEntry::make('domain')
                    ->label('域名')
                    ->state(fn() => $this->domain),
            ]);
    }

    public function getRelationManagers(): array
    {
        return [
            DnsRelationManager::class,
        ];
    }
}
