<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources\Tenants;

use App\Filament\Actions\Tenant\TenantRenewalAction;
use App\Filament\Backend\Clusters\Tenants\TenantsCluster;
use App\Filament\Forms\Components\CustomUpload;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Overtrue\Pinyin\Pinyin;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::GlobeAlt;

    protected static ?string $cluster = TenantsCluster::class;

    protected static ?string $modelLabel = '租户';

    protected static ?string $navigationLabel = '租户管理';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Fieldset::make('基本信息')
                    ->columns()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('租户名称')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function(Set $set, ?string $state) {
                                if (!blank($state)) {
                                    $set('slug', Pinyin::abbr($state)->join(''));
                                }
                            })
                            ->required(),
                        Forms\Components\TextInput::make('slug')
                            ->label('租户简称')
                            ->helperText('涉及到登录地址，域名等信息，全局需唯一')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\DatePicker::make('expired_at')
                            ->label('过期时间')
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->default(now()->addYear())
                            ->required(),
                        CustomUpload::make('avatar')
                            ->label('租户LOGO')
                            ->avatar()
                            ->imageEditor()
                            ->imageResizeTargetWidth(200)
                            ->imageResizeTargetHeight(200),
                    ]),
                Schemas\Components\Fieldset::make('扩展配置')
                    ->columns()
                    ->schema([
                    ]),
                Forms\Components\Toggle::make('status')
                    ->label('状态')
                    ->required()
                    ->default(true)
                    ->inline(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('LOGO')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('租户名称')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('简称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('administrators_count')
                    ->counts('administrators')
                    ->label('租户人数'),
                Tables\Columns\IconColumn::make('status')
                    ->label('状态'),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('到期时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                TenantRenewalAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\ForceDeleteAction::make(),
                Actions\RestoreAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\ForceDeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StaffersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTenants::route('/'),
            'view' => Pages\ViewTenant::route('/{record}'),
        ];
    }
}
