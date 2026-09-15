<?php

namespace App\Console\Commands\Seeders;

use App\Console\Commands\BaseCommand;
use App\Contracts\Attributes\CommandLabel;
use App\Models\System\FailedJob;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

#[Signature('seed:failed-jobs')]
#[CommandLabel('失败任务填充')]
class FailedJobSeeder extends BaseCommand
{
    public function handle(): void
    {
        $count = (int) text(
            label: '要生成的失败任务数量',
            default: '10',
            validate: fn ($value) => is_numeric($value) && $value > 0 ? null : '请输入大于 0 的数字',
        );

        if (confirm('是否清空现有失败任务数据?', default: false)) {
            DB::table('failed_jobs')->truncate();
            $this->info('已清空现有数据');
        }

        $created = [];

        $this->withProgressBar(range(1, $count), function () use (&$created): void {
            $failedJob = FailedJob::factory()->create();
            $created[] = [
                $failedJob->id,
                $failedJob->uuid,
                $failedJob->connection,
                $failedJob->queue,
                $failedJob->payload['job'] ?? 'Unknown',
                $failedJob->failed_at,
            ];
        });

        $this->newLine();
        $this->info("已成功生成 {$count} 条失败任务测试数据：");
        $this->table(
            ['ID', 'UUID', '连接', '队列', '任务', '失败时间'],
            $created
        );

        $this->newLine();
        $this->comment('提示：使用 php artisan queue:failed 查看失败任务列表');
    }
}
