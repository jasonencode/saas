<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources\TenantResource\Pages;

use App\Filament\Actions\DisableBulkAction;
use App\Filament\Actions\EnableBulkAction;
use App\Filament\Backend\Clusters\Tenants\Resources\TenantResource;
use App\Filament\Forms\Components\CustomUpload;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Overtrue\Pinyin\Pinyin;

class ManageTenant extends ManageRecords
{
    protected static string $resource = TenantResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('基本信息')
                    ->columns()
                    ->schema([
                        TextInput::make('name')
                            ->label('团队名称')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function(Set $set, ?string $state) {
                                if (!blank($state)) {
                                    $set('slug', Pinyin::abbr($state)->join(''));
                                }
                            })
                            ->required(),
                        TextInput::make('slug')
                            ->label('团队简称')
                            ->helperText('涉及到登录地址，域名等信息，全局需唯一')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->unique(ignoreRecord: true),
                        DatePicker::make('expired_at')
                            ->label('过期时间')
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->default(now()->addYear())
                            ->required(),
                        CustomUpload::make('avatar')
                            ->label('团队LOGO')
                            ->avatar()
                            ->imageEditor()
                            ->imageResizeTargetWidth(200)
                            ->imageResizeTargetHeight(200),
                    ]),
                Toggle::make('status')
                    ->label('状态')
                    ->required()
                    ->default(true)
                    ->inline(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function(Builder $query): Builder {
                return $query->latest();
            })
            ->columns([
                ImageColumn::make('avatar')
                    ->label('LOGO')
                    ->circular(),
                TextColumn::make('name')
                    ->label('团队名称')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('简称')
                    ->searchable(),
                TextColumn::make('administrators_count')
                    ->counts('administrators')
                    ->label('团队人数'),
                IconColumn::make('status')
                    ->label('状态'),
                TextColumn::make('expired_at')
                    ->label('到期时间'),
                TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
                DisableBulkAction::make(),
                EnableBulkAction::make(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
