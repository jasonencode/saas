<?php

namespace App\Console\Commands\Seeders;

use App\Models\System\FailedJob;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

#[Signature('seed:failed-jobs')]
class FailedJobSeeder extends Command
{
    const array QUEUES = ['default', 'high', 'low', 'emails', 'notifications'];

    const array CONNECTIONS = ['database', 'redis'];

    const array JOB_CLASSES = [
        'App\\Jobs\\Mall\\ProcessOrderPayment',
        'App\\Jobs\\Mall\\SyncInventory',
        'App\\Jobs\\Campaign\\SendCouponNotification',
        'App\\Jobs\\Content\\GenerateThumbnail',
        'App\\Jobs\\Blockchain\\DeployContract',
        'App\\Jobs\\Finance\\GenerateInvoice',
    ];

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
            $created[] = $this->createFailedJob();
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

    private function createFailedJob(): array
    {
        $jobClass = fake()->randomElement(self::JOB_CLASSES);
        $uuid = (string) Str::uuid();
        $displayName = $jobClass;

        $payload = [
            'uuid' => $uuid,
            'displayName' => $displayName,
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'maxTries' => null,
            'maxExceptions' => null,
            'failOnTimeout' => true,
            'tries' => fake()->randomElement([1, 2, 3]),
            'data' => [
                'commandName' => $displayName,
                'command' => fake()->regexify('[A-Za-z0-9+/=]{80}'),
            ],
            'backoff' => fake()->randomElement([null, 30, 60]),
        ];

        $exceptionClass = fake()->randomElement([
            MaxAttemptsExceededException::class,
            'App\\Exceptions\\PaymentFailedException',
            'GuzzleHttp\\Exception\\ConnectionException',
            QueryException::class,
            ServiceUnavailableHttpException::class,
        ]);

        $exceptionMessage = fake()->randomElement([
            'A temporary failure has occurred. Please try again later.',
            'SQLSTATE[HY000] [2002] Connection refused',
            'The payment could not be processed at this time.',
            'Maximum attempts exceeded. The job may have timed out.',
            'Remote service returned 503 Service Unavailable',
        ]);

        $exception = sprintf(
            "%s: %s in /var/www/app/Jobs/ExampleJob.php:%d\n\n".
            "[stacktrace]\n%s\n\n".
            "  +%.2fs %s\n  +%.2fs %s\n".
            "%s",
            $exceptionClass,
            $exceptionMessage,
            fake()->numberBetween(20, 80),
            fake()->paragraph(),
            fake()->randomFloat(2, 0.01, 5),
            $jobClass,
            fake()->randomFloat(2, 0.01, 3),
            'Illuminate\\Queue\\CallQueuedHandler->handle()',
            fake()->paragraph(),
        );

        $failedJob = FailedJob::create([
            'uuid' => $uuid,
            'connection' => fake()->randomElement(self::CONNECTIONS),
            'queue' => fake()->randomElement(self::QUEUES),
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'exception' => $exception,
            'failed_at' => fake()->dateTimeBetween('-7 days', 'now')->format('Y-m-d H:i:s'),
        ]);

        return [
            $failedJob->id,
            $failedJob->uuid,
            $failedJob->connection,
            $failedJob->queue,
            $displayName,
            $failedJob->failed_at,
        ];
    }
}
