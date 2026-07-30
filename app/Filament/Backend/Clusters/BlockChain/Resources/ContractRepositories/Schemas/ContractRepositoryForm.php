<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\ContractRepositories\Schemas;

use App\Models\BlockChain\ContractRepository;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ContractRepositoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->columns(1)
                    ->schema([
                        Section::make('基本信息')
                            ->schema([
                                Grid::make(1)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('合约名称')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Set $set, ?string $state, ?ContractRepository $record): void {
                                                if ($record !== null) {
                                                    return;
                                                }

                                                $set('slug', Str::slug($state ?? ''));
                                            }),
                                        Forms\Components\TextInput::make('slug')
                                            ->label('唯一标识')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true),
                                        Forms\Components\TextInput::make('version')
                                            ->label('版本号')
                                            ->default('1.0.0')
                                            ->required()
                                            ->maxLength(32),
                                        Forms\Components\TextInput::make('contract_name')
                                            ->label('主合约名')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('compiler_version')
                                            ->label('Solidity 版本')
                                            ->maxLength(32)
                                            ->placeholder('例如 0.8.28'),
                                        Forms\Components\TextInput::make('license')
                                            ->label('协议')
                                            ->maxLength(64)
                                            ->placeholder('例如 MIT'),
                                        Forms\Components\Toggle::make('status')
                                            ->label(__('backend.status'))
                                            ->default(true),
                                    ]),
                            ]),
                        Section::make('编译产物')
                            ->schema([
                                Forms\Components\Textarea::make('abi')
                                    ->label('ABI')
                                    ->rows(10)
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('bytecode')
                                    ->label('Bytecode')
                                    ->rows(10)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Grid::make(1)
                    ->columns(1)
                    ->schema([
                        Section::make('源码文件')
                            ->schema([
                                FileUpload::make('source_path')
                                    ->label('上传 .sol 文件')
                                    ->disk('local')
                                    ->directory('contracts/source')
                                    ->visibility('private')
                                    ->moveFiles()
                                    ->rules([
                                        'extensions:sol',
                                        'max:5120',
                                    ])
                                    ->helperText('仅支持 .sol 文件，最大 5MB')
                                    ->downloadable()
                                    ->openable()
                                    ->preserveFilenames()
                                    ->afterStateUpdated(function (FileUpload $component, Set $set, mixed $state): void {
                                        if ($state instanceof TemporaryUploadedFile) {
                                            $set('source_name', $state->getClientOriginalName());
                                            $set('source_size', $state->getSize() ?? 0);
                                            $set('source_code', $state->get());

                                            return;
                                        }

                                        if (blank($state)) {
                                            $set('source_name', null);
                                            $set('source_size', 0);
                                            $set('source_code', null);

                                            return;
                                        }

                                        if (is_array($state)) {
                                            $state = reset($state);
                                        }

                                        if (!is_string($state)) {
                                            return;
                                        }

                                        $disk = Storage::disk($component->getDiskName());

                                        $set('source_name', basename($state));
                                        $set('source_size', $disk->exists($state) ? $disk->size($state) : 0);
                                        $set('source_code', $disk->exists($state) ? $disk->get($state) : null);
                                    })
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                        return sprintf(
                                            '%s_%s.%s',
                                            now()->format('YmdHis'),
                                            Str::random(12),
                                            strtolower($file->getClientOriginalExtension())
                                        );
                                    }),
                                Grid::make(1)
                                    ->schema([
                                        Forms\Components\TextInput::make('source_name')
                                            ->label('源文件名')
                                            ->readOnly(),
                                        Forms\Components\TextInput::make('source_size')
                                            ->label('文件大小')
                                            ->numeric()
                                            ->readOnly(),
                                    ]),
                                Forms\Components\Textarea::make('source_code')
                                    ->label('Sol 源码')
                                    ->required()
                                    ->rows(10)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('补充信息')
                            ->schema([
                                Forms\Components\TagsInput::make('tags')
                                    ->label('标签')
                                    ->placeholder('输入后回车'),
                                Forms\Components\Textarea::make('description')
                                    ->label('描述')
                                    ->rows(4)
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('remark')
                                    ->label('备注')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
