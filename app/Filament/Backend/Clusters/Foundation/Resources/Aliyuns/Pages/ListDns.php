<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\Pages;

use App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\AliyunResource;
use App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\RelationManagers\DnsRelationManager;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class ListDns extends ViewRecord
{
    protected static string $resource = AliyunResource::class;

    public ?string $domain = null;

    public function mount(string|int $record): void
    {
        parent::mount($record);
        $this->domain = request()->route('domain');
    }

    public function getTitle(): string|Htmlable
    {
        return $this->domain.' 解析记录';
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextEntry::make('domain')
                    ->label('域名')
                    ->copyable()
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
