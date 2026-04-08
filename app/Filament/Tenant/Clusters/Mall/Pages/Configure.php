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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Configure extends Page
{
    protected string $view = 'filament.tenant.clusters.mall.pages.store-configure';

    protected static ?string $cluster = MallCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog8Tooth;

    protected static ?string $navigationLabel = '店铺配置';

    protected static ?string $title = '店铺配置';

    protected static ?int $navigationSort = 1;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getRecord()?->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Fieldset::make('基础信息')
                        ->components([
                            Forms\Components\Hidden::make('tenant_id')
                                ->default(Filament::getTenant()->getKey()),
                            Forms\Components\TextInput::make('store_name')
                                ->label('店铺名称')
                                ->required()
                                ->maxLength(255),
                            CustomUpload::make()
                                ->label('店铺LOGO')
                                ->avatar(),
                            Forms\Components\Textarea::make('store_description')
                                ->label('店铺描述')
                                ->maxLength(255)
                                ->rows(4)
                                ->columnSpanFull(),
                        ]),
                    Fieldset::make('配置')
                        ->components([
                            Forms\Components\Select::make('default_express_id')
                                ->label('默认发货快递')
                                ->options(fn () => Express::bySort()->pluck('name', 'id'))
                                ->preload()
                                ->searchable(),
                            Forms\Components\Select::make('auto_complete_days')
                                ->label('自动完成天数')
                                ->options([
                                    7 => '7天自动完成',
                                    14 => '14天自动完成',
                                    30 => '30天自动完成',
                                ])
                                ->preload()
                                ->searchable(),
                            Forms\Components\TextInput::make('order_expired_minutes')
                                ->label('订单自动取消时间')
                                ->required()
                                ->integer()
                                ->minValue(3)
                                ->default(60)
                                ->maxValue(1440),
                        ]),
                    Fieldset::make('联系方式')
                        ->components([
                            Forms\Components\TextInput::make('contactor')
                                ->label('联系人'),
                            Forms\Components\TextInput::make('phone')
                                ->label('电话'),
                        ]),
                    Fieldset::make('地址信息')
                        ->components([
                            AddressSelect::make(),
                        ]),
                ])
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
            ->record($this->getRecord())
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $record = $this->getRecord();

        if (!$record) {
            $record = new StoreConfigure;
        }

        $record->fill($data);
        $record->save();

        Notification::make()
            ->success()
            ->title('店铺配置保存成功')
            ->send();
    }

    public function getRecord(): ?StoreConfigure
    {
        return StoreConfigure::whereBelongsTo(Filament::getTenant())->first();
    }
}
