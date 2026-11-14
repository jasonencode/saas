<?php

namespace Tests\Feature;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\RechargeOrderStatus;
use App\Enums\Finance\RechargeOrderType;
use App\Enums\Finance\WithdrawGateway;
use App\Enums\Finance\WithdrawOrderStatus;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\PaymentRefund;
use App\Models\Finance\RechargeOrder;
use App\Models\Finance\WithdrawOrder;
use App\Models\Mall\Order;
use App\Models\Mall\Refund;
use App\Models\Model;
use App\Models\User\User;
use App\Support\Sigma\Sigma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderNoPrefixTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 业务单号前缀
     *
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function orderNoPrefixProvider(): array
    {
        return [
            '商城订单' => [Order::class, 'SL'],
            '商城售后单' => [Refund::class, 'RF'],
            '支付单' => [PaymentOrder::class, 'PA'],
            '支付退款单' => [PaymentRefund::class, 'PR'],
            '充值单' => [RechargeOrder::class, 'RC'],
            '提现单' => [WithdrawOrder::class, 'WD'],
        ];
    }

    /**
     * 单号带业务前缀且校验位有效
     *
     * @param  class-string  $modelClass
     */
    #[DataProvider('orderNoPrefixProvider')]
    public function test_order_no_uses_business_prefix(string $modelClass, string $prefix): void
    {
        $number = $this->createModel($modelClass)->no;

        $this->assertStringStartsWith($prefix, $number);
        $this->assertSame(20, strlen($number));
        $this->assertTrue(Sigma::verify($number, strlen($prefix)));
    }

    /**
     * 创建待测模型实例
     *
     * @param  class-string  $modelClass
     */
    protected function createModel(string $modelClass): Model
    {
        $userId = User::factory()->create()->id;

        return match ($modelClass) {
            Order::class => Order::factory()->create(['user_id' => $userId]),
            Refund::class => Refund::create([
                'user_id' => $userId,
                'order_id' => Order::factory()->create(['user_id' => $userId])->id,
                'total' => 10,
                'goods_amount' => 10,
                'freight_amount' => 0,
            ]),
            PaymentOrder::class => PaymentOrder::create([
                'user_id' => $userId,
                'gateway' => PaymentGateway::Manual,
                'status' => PaymentStatus::Pending,
                'amount' => 10,
                'expired_at' => now()->addHour(),
            ]),
            PaymentRefund::class => PaymentRefund::create([
                'payment_order_id' => PaymentOrder::create([
                    'user_id' => $userId,
                    'gateway' => PaymentGateway::Manual,
                    'status' => PaymentStatus::Paid,
                    'amount' => 10,
                    'paid_at' => now(),
                ])->id,
                'amount' => 5,
                'reason' => '测试退款',
                'status' => PaymentRefundStatus::Pending,
            ]),
            RechargeOrder::class => RechargeOrder::create([
                'user_id' => $userId,
                'type' => RechargeOrderType::Balance,
                'gateway' => PaymentGateway::Manual,
                'status' => RechargeOrderStatus::Pending,
                'amount' => 10,
                'received_amount' => 10,
                'expired_at' => now()->addHour(),
            ]),
            WithdrawOrder::class => WithdrawOrder::create([
                'user_id' => $userId,
                'gateway' => WithdrawGateway::Bank,
                'status' => WithdrawOrderStatus::Pending,
                'amount' => 10,
                'fee' => 0,
                'actual_amount' => 10,
                'account_info' => ['name' => '测试', 'account' => '6222020000000000000'],
            ]),
        };
    }
}
