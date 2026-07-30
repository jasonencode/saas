<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Contracts\Schemas;

use App\Models\BlockChain\Contract;
use Filament\Infolists;
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
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label(__('backend.tenant'))
                            ->badge(),
                        Infolists\Components\TextEntry::make('name')
                            ->label('合约名称'),
                        Infolists\Components\TextEntry::make('deployer.address')
                            ->label('部署账户')
                            ->copyable()
                            ->copyMessage('已复制部署账户'),
                        Infolists\Components\TextEntry::make('deployer.network.name')
                            ->label('所属网络')
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('deployer.network.type')
                            ->label('网络类型')
                            ->badge(),
                        Infolists\Components\TextEntry::make('status')
                            ->label('合约状态')
                            ->badge()
                            ->getStateUsing(fn (Contract $record): string => $record->address ? '已部署' : '未部署')
                            ->color(fn (Contract $record): string => $record->address ? 'success' : 'warning'),
                    ]),
                // 链上信息
                Section::make('链上信息')
                    ->icon(Heroicon::OutlinedLink)
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('address')
                            ->label('合约地址')
                            ->copyable()
                            ->copyMessage('已复制合约地址')
                            ->placeholder('尚未部署'),
                        Infolists\Components\TextEntry::make('hash')
                            ->label('部署交易哈希')
                            ->copyable()
                            ->copyMessage('已复制交易哈希')
                            ->placeholder('尚未部署'),
                        Infolists\Components\TextEntry::make('parameter')
                            ->label('部署参数')
                            ->placeholder('无')
                            ->columnSpanFull(),
                    ]),
                // 代码信息
                Section::make('代码信息')
                    ->icon(Heroicon::OutlinedCodeBracket)
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        Infolists\Components\TextEntry::make('bytecode')
                            ->label('合约字节码')
                            ->html()
                            ->copyable()
                            ->copyMessage('已复制字节码')
                            ->limit(500)
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('abi')
                            ->label('ABI')
                            ->html()
                            ->copyable()
                            ->copyMessage('已复制 ABI')
                            ->limit(500)
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('original')
                            ->label('合约源代码')
                            ->html()
                            ->copyable()
                            ->copyMessage('已复制源代码')
                            ->limit(500)
                            ->columnSpanFull(),
                    ]),
                Infolists\Components\TextEntry::make('remark')
                    ->label('备注信息')
                    ->placeholder('无')
                    ->columnSpanFull(),
            ]);
    }
}
