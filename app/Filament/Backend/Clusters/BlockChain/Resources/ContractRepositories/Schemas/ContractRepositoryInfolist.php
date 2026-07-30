<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\ContractRepositories\Schemas;

use Filament\Infolists;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Number;

class ContractRepositoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本信息')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('合约名称'),
                        Infolists\Components\TextEntry::make('slug')
                            ->label('唯一标识')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('version')
                            ->label('版本号')
                            ->badge(),
                        Infolists\Components\TextEntry::make('contract_name')
                            ->label('主合约名')
                            ->placeholder('未设置'),
                        Infolists\Components\TextEntry::make('compiler_version')
                            ->label('Solidity 版本')
                            ->placeholder('未设置'),
                        Infolists\Components\TextEntry::make('license')
                            ->label('协议')
                            ->placeholder('未设置'),
                        Infolists\Components\TextEntry::make('status')
                            ->label(__('backend.status'))
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? '启用' : '禁用')
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                    ]),
                Section::make('源码信息')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema([
                        Infolists\Components\TextEntry::make('source_name')
                            ->label('源文件名')
                            ->placeholder('未上传'),
                        Infolists\Components\TextEntry::make('source_size')
                            ->label('文件大小')
                            ->formatStateUsing(fn ($state): string => Number::fileSize((int) $state))
                            ->placeholder('0 B'),
                        Infolists\Components\TextEntry::make('source_path')
                            ->label('存储路径')
                            ->copyable()
                            ->placeholder('未上传')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('source_code')
                            ->label('Sol 源码')
                            ->html()
                            ->copyable()
                            ->copyMessage('已复制源码')
                            ->columnSpanFull(),
                    ]),
                Section::make('编译产物')
                    ->icon(Heroicon::OutlinedCodeBracket)
                    ->schema([
                        Infolists\Components\TextEntry::make('abi')
                            ->label('ABI')
                            ->html()
                            ->copyable()
                            ->copyMessage('已复制 ABI')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('bytecode')
                            ->label('Bytecode')
                            ->html()
                            ->copyable()
                            ->copyMessage('已复制 Bytecode')
                            ->columnSpanFull(),
                    ]),
                Section::make('补充信息')
                    ->icon(Heroicon::OutlinedTag)
                    ->schema([
                        Infolists\Components\TextEntry::make('tags')
                            ->label('标签')
                            ->badge()
                            ->separator(', ')
                            ->placeholder('无'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('描述')
                            ->placeholder('无')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('remark')
                            ->label('备注')
                            ->placeholder('无')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
