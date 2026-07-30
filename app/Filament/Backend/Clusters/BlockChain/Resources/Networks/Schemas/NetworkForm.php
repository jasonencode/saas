<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Networks\Schemas;

use App\Enums\BlockChain\ChainType;
use App\Filament\Forms\Components\TenantSelect;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class NetworkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantSelect::make(),
                Forms\Components\TextInput::make('name')
                    ->label('网络名称')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('网络类型')
                    ->options(ChainType::class)
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('rpc_url')
                    ->label('RPC 地址')
                    ->required()
                    ->url(),
                Forms\Components\TextInput::make('explorer_url')
                    ->label('浏览器地址')
                    ->url(),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status')),
                Schemas\Components\Section::make('链配置')
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => filled($get('type')))
                    ->columns()
                    ->components(fn (Get $get): array => static::getConfigFields($get('type')))
                    ->collapsed(false)
                    ->collapsible(),
            ]);
    }

    private static function getConfigFields(ChainType|string|null $type): array
    {
        if (is_string($type)) {
            $type = ChainType::tryFrom($type);
        }

        if ($type === null) {
            return [];
        }

        $fields = [];

        foreach ($type->configFields() as $key => $def) {
            $field = match ($def['type']) {
                'textarea' => Forms\Components\Textarea::make('config.'.$key)
                    ->rows(5)
                    ->columnSpan($def['columnSpan']),
                'toggle' => Forms\Components\Toggle::make('config.'.$key)
                    ->inline(false)
                    ->columnSpan($def['columnSpan']),
                default => Forms\Components\TextInput::make('config.'.$key)
                    ->columnSpan($def['columnSpan']),
            };

            $field
                ->label($def['label'])
                ->helperText($def['help'] ?? '');

            if ($def['type'] !== 'toggle') {
                $field->placeholder($def['placeholder'] ?? '');
            }

            $fields[] = $field;
        }

        return $fields;
    }
}
