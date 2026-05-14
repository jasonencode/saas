<?php

namespace App\Filament\Tenant\Clusters\BlockChain\Resources\Contracts\Schemas;

use App\Models\BlockChain\Contract;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ContractInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 基本信息
                Section::make('基本信息')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')
                            ->label('合约名称'),
                        TextEntry::make('deployer.address')
                            ->label('部署账户')
                            ->copyable()
                            ->copyMessage('已复制部署账户'),
                        TextEntry::make('deployer.network.name')
                            ->label('所属网络')
                            ->badge()
                            ->color('success'),
                        TextEntry::make('deployer.network.type')
                            ->label('网络类型')
                            ->badge(),
                        TextEntry::make('status')
                            ->label('合约状态')
                            ->badge()
                            ->getStateUsing(fn (Contract $record): string => $record->address ? '已部署' : '未部署')
                            ->color(fn (Contract $record): string => $record->address ? 'success' : 'warning'),
                    ]),
                // 链上信息
                Section::make('链上信息')
                    ->icon(Heroicon::OutlinedCubeTransparent)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('address')
                            ->label('合约地址')
                            ->copyable()
                            ->copyMessage('已复制合约地址')
                            ->columnSpanFull(),
                        TextEntry::make('hash')
                            ->label('上链哈希')
                            ->copyable()
                            ->copyMessage('已复制交易哈希')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Contract $record): bool => filled($record->address)),
                // 代码信息
                Section::make('代码信息')
                    ->icon(Heroicon::OutlinedCodeBracket)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('bytecode')
                            ->label('合约代码')
                            ->html()
                            ->copyable()
                            ->copyMessage('已复制合约代码')
                            ->limit(500)
                            ->columnSpanFull(),
                        TextEntry::make('abi')
                            ->label('ABI')
                            ->html()
                            ->copyable()
                            ->copyMessage('已复制 ABI')
                            ->limit(500)
                            ->columnSpanFull(),
                        TextEntry::make('original')
                            ->label('合约源代码')
                            ->html()
                            ->copyable()
                            ->copyMessage('已复制源代码')
                            ->limit(500)
                            ->columnSpanFull(),
                    ]),
                TextEntry::make('remark')
                    ->label('备注信息')
                    ->placeholder('无')
                    ->columnSpanFull(),
            ]);
    }
}
