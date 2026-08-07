<?php

namespace App\Console\Commands\Maintenance;

use App\Models\Mall;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

#[Signature('maintenance:clear-data {--force : 跳过确认提示}')]
#[Description('清理指定模型的数据，配置见 commands() 方法')]
class ClearDataCommand extends Command
{
    /**
     * 需要清理的模型配置
     *
     * @var array<class-string<Model>>
     */
    protected array $models = [
        // Order 相关
        Mall\OrderLog::class,
        Mall\OrderShipping::class,
        Mall\OrderAddress::class,
        Mall\OrderItem::class,
        Mall\Order::class,

        // Refund 相关
        Mall\RefundLog::class,
        Mall\RefundExpress::class,
        Mall\RefundItem::class,
        Mall\Refund::class,
    ];

    public function handle(): int
    {
        $models = $this->models;

        if (empty($models)) {
            $this->warn('没有配置需要清理的模型。');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('确定要清理这些模型的数据吗？')) {
            $this->info('操作已取消。');

            return self::SUCCESS;
        }

        foreach ($models as $model) {
            $this->clearModel($model);
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
}
