<?php

namespace App\Filament\Tenant\Clusters\BlockChain\Resources\Networks\Schemas;

use App\Models\BlockChain\Network;
use Filament\Infolists;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class NetworkInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\TextEntry::make('name')
                    ->label('网络名称'),
                Infolists\Components\TextEntry::make('type')
                    ->label('主网类型')
                    ->badge(),
                Infolists\Components\TextEntry::make('rpc_url')
                    ->label('RPC 地址'),
                Infolists\Components\TextEntry::make('explorer_url')
                    ->label('浏览器地址')
                    ->color('info')
                    ->url(fn (Network $network) => $network->explorer_url, true),
                Infolists\Components\TextEntry::make('config')
                    ->label('配置信息')
                    ->visible(fn (Network $network) => ! empty($network->config))
                    ->state(fn (Network $network): string => static::formatConfig($network->config)),
            ]);
    }

    private static function formatConfig(mixed $config): string
    {
        if ($config instanceof Collection) {
            $config = $config->toArray();
        }

        if (! is_array($config) || empty($config)) {
            return '';
        }

        $lines = [];

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $lines[] = static::formatConfigLine($key.'.'.$subKey, $subValue);
                }
            } else {
                $lines[] = static::formatConfigLine((string) $key, $value);
            }
        }

        return implode("\n", $lines);
    }

    private static function formatConfigLine(string $label, mixed $value): string
    {
        $display = is_string($value) && strlen($value) > 60
            ? substr($value, 0, 60).'...'
            : (is_string($value) ? $value : json_encode($value));

        return "**$label**：$display";
    }
}
