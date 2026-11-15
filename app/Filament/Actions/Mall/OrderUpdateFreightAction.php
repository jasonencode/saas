<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\Mall\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class OrderUpdateFreightAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderUpdateFreight';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('修改运费');
        $this->icon(Heroicon::OutlinedBanknotes);
        $this->color('warning');

        $this->visible(fn (Order $order): bool => userCan(self::getDefaultName(), $order) && $order->status === OrderStatus::Pending);

        $this->fillForm(fn (Order $order): array => [
            'freight' => (string) $order->freight,
        ]);

        $this->schema([
            Forms\Components\TextInput::make('freight')
                ->label('运费')
                ->numeric()
                ->minValue(0)
                ->prefix('￥')
                ->required(),
        ]);

        $this->action(function (Order $order, OrderService $service, array $data): void {
            try {
                $service->updateFreight($order, bcadd((string) $data['freight'], '0', 2), Filament::auth()->user());

                $this->successNotificationTitle('运费已修改');
                $this->success();
            } catch (InvalidArgumentException|RuntimeException $e) {
                $this->failureNotificationTitle('修改运费失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            } catch (Throwable $e) {
                report($e);

                $this->failureNotificationTitle('修改运费失败');
                $this->failureNotificationBody('系统异常，请稍后重试');
                $this->failure();
            }
        });
    }
}
