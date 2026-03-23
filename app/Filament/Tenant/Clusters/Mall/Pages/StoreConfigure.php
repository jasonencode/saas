<?php

namespace App\Filament\Tenant\Clusters\Mall\Pages;

use App\Filament\Forms\Components\AddressSelect;
use App\Filament\Forms\Components\CustomUpload;
use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Models\Express;
use App\Models\StoreConfigure as ConfigureModel;
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

class StoreConfigure extends Page
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
                            Forms\Components\TextInput::make('name')
                                ->label('店铺名称')
                                ->required()
                                ->maxLength(255),
                            CustomUpload::make()
                                ->label('店铺LOGO')
                                ->avatar(),
                            Forms\Components\Textarea::make('description')
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
            $record = new ConfigureModel();
        }

        $record->fill($data);
        $record->save();

        if ($record->wasRecentlyCreated) {
            $this->form->record($record)->saveRelationships();
        }

        Notification::make()
            ->success()
            ->title('Saved')
            ->send();
    }

    public function getRecord(): ?ConfigureModel
    {
        return ConfigureModel::whereBelongsTo(Filament::getTenant())->first();
    }
}
