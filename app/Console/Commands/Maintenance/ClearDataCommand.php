<?php

namespace App\Console\Commands\Maintenance;

use App\Console\Commands\BaseCommand;
use App\Contracts\Attributes\CommandLabel;
use App\Models;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

#[Signature('maintenance:clear-data {--force : 跳过确认提示}')]
#[Description('清理指定模型的数据，配置见 commands() 方法')]
#[CommandLabel('清理数据')]
class ClearDataCommand extends BaseCommand
{
    /**
     * 需要清理的模型配置
     *
     * @var array<class-string<Model>>
     */
    protected array $models = [
        // ===== Campaign =====
        Models\Campaign\LotteryPrizeRecord::class,
        Models\Campaign\LotteryDraw::class,
        Models\Campaign\LotteryPrize::class,
        Models\Campaign\Lottery::class,
        Models\Campaign\RedpackCode::class,
        Models\Campaign\Redpack::class,
        Models\Campaign\CouponUser::class,
        Models\Campaign\CouponProduct::class,
        Models\Campaign\CouponOrder::class,
        Models\Campaign\Coupon::class,

        // ===== Content =====
        Models\Content\SuggestMessage::class,
        Models\Content\Suggest::class,
        Models\Content\Comment::class,
        Models\Content\ContentTag::class,
        Models\Content\ContentCategory::class,
        Models\Content\Content::class,
        Models\Content\SinglePage::class,
        Models\Content\Notification::class,
        Models\Content\AppVersion::class,
        Models\Content\Tag::class,
        Models\Content\Category::class,

        // ===== Foundation =====
        Models\Foundation\SocialiteAccount::class,
        Models\Foundation\Socialite::class,

        // ===== Mall - Order =====
        Models\Mall\OrderLog::class,
        Models\Mall\OrderShipping::class,
        Models\Mall\OrderAddress::class,
        Models\Mall\OrderItem::class,
        Models\Mall\Order::class,

        // ===== Mall - Refund =====
        Models\Mall\RefundLog::class,
        Models\Mall\RefundExpress::class,
        Models\Mall\RefundItem::class,
        Models\Mall\Refund::class,

        // ===== Mall - Cart =====
        Models\Mall\CartItem::class,
        Models\Mall\Cart::class,

        // ===== Mall - Product =====
        Models\Mall\ProductTag::class,
        Models\Mall\ProductDiscount::class,
        Models\Mall\ProductLog::class,
        Models\Mall\Sku::class,
        Models\Mall\Product::class,
        Models\Mall\ProductCategory::class,

        // ===== Mall - 其他 =====
        Models\Mall\Topic::class,
        Models\Mall\StoreApply::class,
        Models\Mall\ReturnAddress::class,
        Models\Mall\DeliveryRule::class,
        Models\Mall\Delivery::class,
        Models\Mall\Banner::class,
        Models\Mall\Brand::class,
        Models\Mall\Express::class,
        Models\Mall\PickupPoint::class,
        Models\Mall\Supplier::class,
        Models\Mall\StoreConfigure::class,
        Models\Mall\Region::class,

        // ===== Finance =====
        Models\Finance\InvoiceApplicationOrder::class,
        Models\Finance\InvoiceApplication::class,
        Models\Finance\Invoice::class,
        Models\Finance\InvoiceTitle::class,
        Models\Finance\PaymentRefund::class,
        Models\Finance\PaymentOrder::class,
        Models\Finance\RechargeOrder::class,
        Models\Finance\WithdrawOrder::class,
        Models\Finance\VoucherLog::class,
        Models\Finance\Voucher::class,
        Models\Finance\Task::class,
        Models\Finance\Plan::class,
        Models\Finance\UserAccountLog::class,
        Models\Finance\UserAccount::class,

        // ===== System =====
        Models\System\ApiLog::class,
        Models\System\BlackList::class,
        Models\System\Sensitive::class,
        Models\System\DBLog::class,
        Models\System\FailedJob::class,
        Models\System\JobBatch::class,
    ];

    /**
     * 需要清理的中间表（无对应 Eloquent 模型）
     *
     * @var array<string>
     */
    protected array $pivotTables = [
        'failed_import_rows',
        'imports',
        'exports',
        'topic_product',
        'pickup_point_product',
        'favorites',
    ];

    public function handle(): int
    {
        $models = $this->models;

        if (empty($models) && empty($this->pivotTables)) {
            $this->warn('没有配置需要清理的数据。');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('确定要清理这些数据吗？')) {
            $this->info('操作已取消。');

            return self::SUCCESS;
        }

        foreach ($models as $model) {
            $this->clearModel($model);
        }

        foreach ($this->pivotTables as $table) {
            $this->clearTable($table);
        }

        $this->info('清理完毕。');

        return self::SUCCESS;
    }

    /**
     * 清空单个模型对应的表
     */
    protected function clearModel(string $model): void
    {
        /** @var Model $instance */
        $instance = new $model;

        $instance->truncate();

        $this->line("模型 [{$model}] 数据已清空。");
    }

    /**
     * 清空指定表
     */
    protected function clearTable(string $table): void
    {
        DB::table($table)->truncate();

        $this->line("中间表 [{$table}] 数据已清空。");
    }
}
