<?php

namespace App\Filament\Tenant\Clusters\Mall\Pages;

use App\Enums\Mall\OrderStatus;
use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Services\Mall\OrderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;
use UnitEnum;

/**
 * 核销码核销页面
 *
 * 顶部输入核销码，下方动态展示订单信息、金额与商品明细，确认后直接核销。
 * 核销码输入框自动聚焦，兼容扫码枪（模拟键盘输入 + 回车）。
 */
class PickupVerify extends Page implements HasInfolists, HasTable
{
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static ?string $cluster = MallCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?string $navigationLabel = '核销';

    protected static ?string $title = '核销';

    protected static string|UnitEnum|null $navigationGroup = '订单';

    protected static ?int $navigationSort = 25;

    protected string $view = 'filament.pages.pickup-verify';

    /**
     * 核销码输入值
     */
    public string $pickupCode = '';

    /**
     * 查询到的订单（null 表示未查询或未找到）
     */
    public ?Order $order = null;

    /**
     * 查询/核销错误信息
     */
    public ?string $error = null;

    public static function canAccess(): bool
    {
        return MallCluster::isAvailable() && userCan('orderVerify', Order::class);
    }

    /**
     * 点击查询按钮后查找核销码对应的订单
     */
    public function search(): void
    {
        $code = trim($this->pickupCode);

        if ($code === '') {
            $this->order = null;
            $this->error = '请输入核销码';

            return;
        }

        $this->order = Order::with(['items.orderable', 'user', 'pickupPoint'])
            ->where('pickup_code', $code)
            ->first();

        $this->error = $this->order ? null : '核销码不存在';
    }

    /**
     * 清空核销码与查询结果，并聚焦输入框
     */
    public function clear(): void
    {
        $this->pickupCode = '';
        $this->order = null;
        $this->error = null;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->record(fn (): ?Order => $this->order)
            ->columns(3)
            ->schema([
                Section::make('订单信息')
                    ->columns(3)
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('no')
                            ->label('订单编号'),
                        TextEntry::make('user.username')
                            ->label('下单用户')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->label('订单状态')
                            ->badge(),
                        TextEntry::make('pickupPoint.name')
                            ->label('自提点')
                            ->placeholder('-'),
                        TextEntry::make('verifiedBy.name')
                            ->label('核销用户')
                            ->placeholder('-'),
                        TextEntry::make('verified_at')
                            ->label('核销时间')
                            ->dateTime('Y-m-d H:i:s')
                            ->placeholder('-'),
                    ]),
                Section::make('金额信息')
                    ->columns(2)
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('amount')
                            ->label('商品金额')
                            ->money('CNY'),
                        TextEntry::make('freight')
                            ->label('运费')
                            ->money('CNY'),
                        TextEntry::make('total_amount')
                            ->label('订单总额')
                            ->money('CNY')
                            ->size(TextSize::Large)
                            ->color('primary'),
                    ]),
            ]);
    }

    /**
     * 商品明细表格（当前订单的履约商品）
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => OrderItem::query()->where('order_id', $this->order?->id ?? 0))
            ->columns([
                TextColumn::make('orderable_name')
                    ->label('商品'),
                TextColumn::make('price')
                    ->label('单价')
                    ->money('CNY'),
                TextColumn::make('qty')
                    ->label('数量'),
                TextColumn::make('sub_total')
                    ->label('小计')
                    ->money('CNY'),
            ])
            ->paginated(false);
    }

    /**
     * 确认核销按钮（弹窗确认）
     */
    public function verifyAction(): Action
    {
        return Action::make('verify')
            ->label('确认核销')
            ->color('success')
            ->disabled(fn (): bool => !$this->order || $this->order->status !== OrderStatus::PickupPending)
            ->requiresConfirmation()
            ->modalHeading('确认核销')
            ->modalDescription(fn (): ?string => $this->order
                ? sprintf('确认核销订单 %s？', $this->order->no)
                : null)
            ->modalSubmitActionLabel('确认核销')
            ->modalCancelActionLabel('取消')
            ->action(fn () => $this->verify());
    }

    /**
     * 确认核销
     */
    public function verify(): void
    {
        if (!$this->order) {
            $this->error = '请先查询订单';

            return;
        }

        try {
            service(OrderService::class)
                ->verify($this->order, Filament::auth()->user(), trim($this->pickupCode));

            $this->order->refresh();
            $this->error = null;

            Notification::make()
                ->success()
                ->title('核销成功')
                ->send();
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
        }
    }
}
