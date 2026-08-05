<?php

namespace App\Filament\Tenant\Clusters\Mall\Pages;

use App\Enums\Mall\ApplyStatus;
use App\Filament\Forms\Components\CustomUpload;
use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Models\Mall\StoreApply;
use App\Services\Mall\StoreService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Apply extends Page
{
    protected static ?string $cluster = MallCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = '开店申请';

    protected static ?string $title = '开店申请';

    protected static ?int $navigationSort = -10;

    protected string $view = 'filament.pages.form';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return !MallCluster::isAvailable();
    }

    public function mount(): void
    {
        $this->form->fill($this->getRecord()?->attributesToArray() ?? []);
    }

    /**
     * 获取当前租户的店铺申请记录
     */
    public function getRecord(): ?StoreApply
    {
        return StoreApply::whereBelongsTo(Filament::getTenant())
            ->latest()
            ->first();
    }

    public function form(Schema $schema): Schema
    {
        $record = $this->getRecord();

        $disabled = !$this->canSubmit($record);

        return $schema
            ->components([
                Form::make(array_filter([
                    $this->getStatusSection($record),
                    Schemas\Components\Grid::make(1)
                        ->schema([
                            Section::make('店铺信息')
                                ->columns()
                                ->schema([
                                    Forms\Components\Hidden::make('tenant_id')
                                        ->default(Filament::getTenant()->getKey()),
                                    Forms\Components\TextInput::make('store_name')
                                        ->label('店铺名称')
                                        ->required()
                                        ->maxLength(255)
                                        ->disabled($disabled)
                                        ->helperText('将展示在店铺前台、订单及售后等位置。'),
                                    Forms\Components\Textarea::make('store_description')
                                        ->label('店铺描述')
                                        ->required()
                                        ->maxLength(255)
                                        ->rows(4)
                                        ->columnSpanFull()
                                        ->disabled($disabled)
                                        ->helperText('用于简要介绍店铺，最多 255 个字符。'),
                                ]),
                            Section::make('联系人信息')
                                ->columns()
                                ->schema([
                                    Forms\Components\TextInput::make('contactor')
                                        ->label('联系人')
                                        ->required()
                                        ->maxLength(255)
                                        ->disabled($disabled),
                                    Forms\Components\TextInput::make('phone')
                                        ->label('联系电话')
                                        ->required()
                                        ->maxLength(20)
                                        ->regex('/^1[3-9]\d{9}$/')
                                        ->disabled($disabled)
                                        ->helperText('请输入可联系到店铺负责人的手机号码。'),
                                ]),
                            Section::make('资质证件')
                                ->columns(3)
                                ->schema([
                                    CustomUpload::make('front')
                                        ->label('身份证正面（国徽面）')
                                        ->required()
                                        ->image()
                                        ->disabled($disabled)
                                        ->helperText('请上传清晰的身份证国徽面照片。'),
                                    CustomUpload::make('back')
                                        ->label('身份证背面（人像面）')
                                        ->required()
                                        ->image()
                                        ->disabled($disabled)
                                        ->helperText('请上传清晰的身份证人像面照片。'),
                                    CustomUpload::make('license')
                                        ->label('企业营业执照')
                                        ->required()
                                        ->image()
                                        ->disabled($disabled)
                                        ->helperText('请上传清晰的企业营业执照照片。'),
                                ]),
                        ]),
                ]))
                    ->columns()
                    ->livewireSubmitHandler('submit')
                    ->footer([
                        Actions::make([
                            Action::make('submit')
                                ->label($this->getSubmitLabel($record))
                                ->submit('submit')
                                ->keyBindings(['mod+s'])
                                ->visible($this->canSubmit($record)),
                        ]),
                    ]),
            ])
            ->record($record)
            ->statePath('data');
    }

    /**
     * 获取提交按钮标签
     */
    protected function getSubmitLabel(?StoreApply $record): string
    {
        if ($record && $record->status === ApplyStatus::Rejected) {
            return '重新提交';
        }

        return '提交申请';
    }

    /**
     * 是否可提交申请
     *
     * 无申请记录、或上次申请被拒绝时可提交；申请中或已通过时不可提交。
     */
    protected function canSubmit(?StoreApply $record): bool
    {
        if (!$record) {
            return true;
        }

        return $record->status === ApplyStatus::Rejected;
    }

    /**
     * 申请状态展示区块
     *
     * 已有申请记录时，顶部展示当前状态、提交时间，以及拒绝理由或审核备注。
     *
     * @return Section|null 状态区块；无记录时返回 null
     */
    protected function getStatusSection(?StoreApply $record): ?Section
    {
        if (!$record) {
            return null;
        }

        $fields = [
            Text::make($record->status->getLabel())
                ->badge()
                ->color($record->status->getColor()),
            Text::make($record->created_at?->format('Y-m-d H:i:s') ?? '-'),
        ];

        if ($record->status === ApplyStatus::Rejected && $record->reason) {
            $fields[] = Text::make($record->reason)
                ->columnSpanFull();
        } elseif ($record->remark) {
            $fields[] = Text::make($record->remark)
                ->columnSpanFull();
        }

        return Section::make('申请进度')
            ->columns(2)
            ->schema($fields);
    }

    /**
     * 提交店铺申请
     */
    public function submit(StoreService $service): void
    {
        $data = $this->form->getState();

        try {
            $service->createApply(Filament::getTenant(), $data);
        } catch (\DomainException $e) {
            Notification::make()
                ->warning()
                ->title('无法提交申请')
                ->body($e->getMessage())
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('申请提交成功')
            ->body('请耐心等待审核结果，审核通过后即可开通店铺。')
            ->send();

        $this->form->fill($this->getRecord()?->attributesToArray() ?? []);
    }
}
