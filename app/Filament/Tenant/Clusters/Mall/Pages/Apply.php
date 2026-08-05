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
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas;
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
     * 当前租户的最近一条店铺申请记录
     */
    protected function getRecord(): ?StoreApply
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
                Schemas\Components\Form::make(array_filter([
                    Schemas\Components\Grid::make(1)
                        ->schema([
                            Schemas\Components\Section::make('店铺信息')
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
                            Schemas\Components\Section::make('资质证件')
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
                    Schemas\Components\Grid::make(1)
                        ->schema([
                            Schemas\Components\Section::make('联系人信息')
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
                            Schemas\Components\Section::make('审核信息')
                                ->columns()
                                ->schema([
                                    Infolists\Components\TextEntry::make('status')
                                        ->label('审核状态')
                                        ->badge()
                                        ->color(fn ($state) => $state->getColor()),
                                    Infolists\Components\TextEntry::make('created_at')
                                        ->label('申请时间'),
                                    Infolists\Components\TextEntry::make('reason')
                                        ->label('拒绝理由')
                                        ->columnSpanFull()
                                        ->visible(fn ($record) => $record->status === ApplyStatus::Rejected),
                                ]),
                        ]),
                ]))
                    ->columns()
                    ->livewireSubmitHandler('submit')
                    ->footer([
                        Schemas\Components\Actions::make([
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
     * 提交按钮标签：被拒绝时为「重新提交」，其余为「提交申请」
     */
    protected function getSubmitLabel(?StoreApply $record): string
    {
        return $record && $record->status === ApplyStatus::Rejected
            ? '重新提交'
            : '提交申请';
    }

    /**
     * 是否可提交申请
     *
     * 无记录、或上次被拒绝时可提交；申请中或已通过时不可提交。
     */
    protected function canSubmit(?StoreApply $record): bool
    {
        if (!$record) {
            return true;
        }

        return $record->status === ApplyStatus::Rejected;
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
