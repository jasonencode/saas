<?php

namespace App\Console\Commands\Seeders;

use App\Models\Content\Notification;
use App\Models\User\User;
use App\Notifications\Finance\InvoiceApplicationSubmittedNotification;
use App\Notifications\Mall\OrderCreatedNotification;
use App\Notifications\Mall\OrderDeliveredNotification;
use App\Notifications\Mall\OrderPaidNotification;
use App\Notifications\Mall\OrderSignedNotification;
use App\Notifications\Mall\StoreApplyReviewedNotification;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\text;

#[Signature('seed:messages')]
class MessageSeeder extends Command
{
    public function handle(): void
    {
        $userId = (int) text(
            label: '指定用户 ID',
            default: (string) User::query()->min('id'),
            validate: static function (string $value): ?string {
                if (!is_numeric($value) || (int) $value < 1) {
                    return '请输入有效的用户 ID';
                }

                return User::query()->whereKey((int) $value)->exists()
                    ? null
                    : "用户 ID [{$value}] 不存在";
            },
        );

        $count = (int) text(
            label: '生成消息数量',
            default: '10',
            validate: static fn (string $value): ?string => is_numeric($value) && (int) $value > 0 ? null : '请输入大于 0 的数字',
        );

        $user = User::query()->findOrFail($userId);
        $this->info(sprintf('开始为用户 [%s] 生成模拟消息...', $user->name ?? $user->username));

        $templates = $this->templates();
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $template = $templates[array_rand($templates)];
            $createdAt = fake('zh_CN')->dateTimeBetween('-30 days');

            Notification::create([
                'id' => (string) Str::orderedUuid(),
                'type' => $template['class'],
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->getKey(),
                'data' => [
                    'title' => $template['titles'][array_rand($template['titles'])],
                    'body' => $template['body'](),
                    'color' => $template['color'],
                    'icon' => $template['icon'],
                    'iconColor' => $template['color'],
                    'status' => $template['color'],
                ],
                'read_at' => fake()->boolean(60) ? fake()->dateTimeBetween($createdAt) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("模拟消息生成完成，共 {$count} 条");
    }

    /**
     * 模拟消息模板
     *
     * @return array<int, array{
     *     class: class-string,
     *     icon: string,
     *     color: string,
     *     titles: array<int, string>,
     *     body: callable(): string,
     * }>
     */
    private function templates(): array
    {
        return [
            [
                'class' => OrderCreatedNotification::class,
                'icon' => 'shopping-cart',
                'color' => 'info',
                'titles' => ['订单已创建', '订单提交成功', '您的订单已生成'],
                'body' => fn (): string => sprintf('订单编号：%s 已创建，请尽快完成支付', $this->fakeOrderNo()),
            ],
            [
                'class' => OrderPaidNotification::class,
                'icon' => 'check-circle',
                'color' => 'success',
                'titles' => ['订单支付成功', '支付完成', '订单已支付'],
                'body' => fn (): string => sprintf('订单编号：%s 已支付，金额：¥%s', $this->fakeOrderNo(), $this->fakeAmount()),
            ],
            [
                'class' => OrderDeliveredNotification::class,
                'icon' => 'truck',
                'color' => 'warning',
                'titles' => ['订单已发货', '包裹已发出', '您的订单已发货'],
                'body' => fn (): string => sprintf('订单编号：%s 已发货，快递单号：%s，请注意查收', $this->fakeOrderNo(), $this->fakeExpressNo()),
            ],
            [
                'class' => OrderSignedNotification::class,
                'icon' => 'clipboard-check',
                'color' => 'success',
                'titles' => ['订单已签收', '签收成功', '您的订单已签收'],
                'body' => fn (): string => sprintf('订单编号：%s 已确认签收，感谢您的购买，期待再次光临', $this->fakeOrderNo()),
            ],
            [
                'class' => StoreApplyReviewedNotification::class,
                'icon' => 'store',
                'color' => 'success',
                'titles' => ['店铺申请已通过', '入驻审核通过', '您的店铺申请已通过'],
                'body' => fn (): string => '恭喜，您的店铺入驻申请已审核通过，现在可以开始经营了',
            ],
            [
                'class' => InvoiceApplicationSubmittedNotification::class,
                'icon' => 'receipt',
                'color' => 'success',
                'titles' => ['发票申请已提交', '开票申请成功', '您的发票申请已提交'],
                'body' => fn (): string => sprintf('您的发票申请已成功提交，申请金额：¥%s，我们将尽快处理', $this->fakeAmount()),
            ],
        ];
    }

    /**
     * 生成模拟订单号
     */
    private function fakeOrderNo(): string
    {
        return now()->format('Ymd').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * 生成模拟金额
     */
    private function fakeAmount(): string
    {
        return number_format((float) fake('zh_CN')->randomFloat(2, 1, 9999), 2);
    }

    /**
     * 生成模拟快递单号
     */
    private function fakeExpressNo(): string
    {
        return strtoupper(Str::random(2)).str_pad((string) random_int(0, 9999999999), 12, '0', STR_PAD_LEFT);
    }
}
