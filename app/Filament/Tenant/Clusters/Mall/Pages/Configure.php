<?php

namespace App\Filament\Tenant\Clusters\Mall\Pages;

use App\Filament\Forms\Components\AddressSelect;
use App\Filament\Forms\Components\CustomUpload;
use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Models\Mall\Express;
use App\Models\Mall\StoreConfigure;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Configure extends Page
{
    protected static ?string $cluster = MallCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog;

    protected static ?string $navigationLabel = '店铺配置';

    protected static ?string $title = '店铺配置';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->data = $this->getRecord()?->attributesToArray();
    }

    public function getRecord(): ?StoreConfigure
    {
        return StoreConfigure::whereBelongsTo(Filament::getTenant())->first();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Grid::make(1)
                        ->schema([
                            Fieldset::make('基础信息')
                                ->components([
                                    Forms\Components\Hidden::make('tenant_id')
                                        ->default(Filament::getTenant()->getKey()),
                                    Forms\Components\TextInput::make('store_name')
                                        ->label('店铺名称')
                                        ->helperText('展示在店铺前台、订单和售后等位置。')
                                        ->required()
                                        ->maxLength(255),
                                    CustomUpload::make()
                                        ->label('店铺LOGO')
                                        ->helperText('建议上传清晰的正方形图片，用作店铺LOGO。')
                                        ->avatar(),
                                    Forms\Components\Textarea::make('store_description')
                                        ->label('店铺描述')
                                        ->helperText('用于简要介绍店铺，最多 255 个字符。')
                                        ->maxLength(255)
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ]),
                            Fieldset::make('联系方式')
                                ->components([
                                    Forms\Components\TextInput::make('contactor')
                                        ->label('联系人')
                                        ->helperText('用于订单、售后和店铺联系信息展示。'),
                                    Forms\Components\TextInput::make('phone')
                                        ->label('电话')
                                        ->helperText('请输入可联系到店铺负责人的电话号码。'),
                                ]),
                        ]),
                    Grid::make(1)
                        ->schema([
                            Fieldset::make('配置')
                                ->components([
                                    Forms\Components\Select::make('default_express_id')
                                        ->label('默认发货快递')
                                        ->helperText('创建发货信息时默认选中的快递公司，可在发货时修改。')
                                        ->options(fn () => Express::bySort()->pluck('name', 'id'))
                                        ->preload()
                                        ->searchable(),
                                    Forms\Components\Select::make('auto_complete_days')
                                        ->label('自动完成天数')
                                        ->helperText('订单发货后超过该天数未确认收货时，系统将自动完成订单。')
                                        ->required()
                                        ->options([
                                            7 => '7天自动完成',
                                            14 => '14天自动完成',
                                            30 => '30天自动完成',
                                        ])
                                        ->preload()
                                        ->searchable(),
                                    Forms\Components\TextInput::make('order_expired_minutes')
                                        ->label('订单自动取消时间')
                                        ->helperText('买家下单后超过该时间未支付时，系统将自动取消订单。')
                                        ->required()
                                        ->integer()
                                        ->minValue(3)
                                        ->default(60)
                                        ->maxValue(1440)
                                        ->suffix('分钟'),
                                ]),
                            Fieldset::make('地址信息')
                                ->components([
                                    AddressSelect::make(),
                                ]),
                        ]),

                ])
                    ->statePath('data')
                    ->columns()
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('保存')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->record($this->getRecord());
    }

    public function save(): void
    {
        $data = $this->content->getState();

        $record = $this->getRecord();

        if (! $record) {
            $record = new StoreConfigure;
        }

        $record->fill($data);
        $record->save();

        Notification::make()
            ->success()
            ->title('店铺配置保存成功')
            ->send();
    }
}
