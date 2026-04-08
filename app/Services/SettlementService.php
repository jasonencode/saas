<?php

namespace App\Services;

use App\Contracts\ServiceInterface;
use App\Contracts\SettleTaskData;
use App\Enums\Finance\VoucherStatus;
use App\Models\Finance\Task;
use App\Models\Finance\Voucher;
use App\Models\Finance\VoucherLog;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Pipeline;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use Throwable;

class SettlementService implements ServiceInterface
{
    /**
     * 执行结算
     *
     * @throws ReflectionException
     * @throws Throwable
     */
    public function execute(Voucher $voucher): bool
    {
        if ($voucher->status === VoucherStatus::Processing) {
            throw new InvalidArgumentException('该凭据正在结算中，请等待');
        }

        if ($voucher->status === VoucherStatus::Success) {
            throw new InvalidArgumentException('该凭据已经结算完成，请勿重复操作');
        }

        $voucher->status = VoucherStatus::Processing;
        $voucher->save();

        DB::beginTransaction();
        try {
            Pipeline::send(new SettleTaskData($voucher))
                ->through($this->getVoucherTasks($voucher))
                ->then(function (SettleTaskData $data) {
                    $data->voucher->status = VoucherStatus::Success;
                    $data->voucher->completed_at = now();
                    $data->voucher->save();

                    return $data->voucher;
                });
            DB::commit();

            return true;
        } catch (Throwable $exception) {
            DB::rollBack();

            $voucher->status = VoucherStatus::Failure;
            $voucher->exception = (string) $exception;
            $voucher->save();

            throw $exception;
        }
    }

    /**
     * 获取凭据任务
     */
    protected function getVoucherTasks(Voucher $voucher): array
    {
        return $voucher->plan
            ->tasks()
            ->ofEnabled()
            ->orderBy('sort')
            ->get()
            ->map(function (Task $task) {
                if (! class_exists($task->service)) {
                    return null;
                }

                $reflection = new ReflectionClass($task->service);
                $instance = $reflection->newInstance($task);

                return static function (SettleTaskData $data, Closure $next) use ($task, $instance) {
                    $log = VoucherLog::create([
                        'voucher_id' => $data->voucher->getKey(),
                        'task_id' => $task->getKey(),
                        'step' => method_exists($instance, 'getTitle') ? $instance->getTitle() : $task->name,
                        'status' => 'started',
                    ]);
                    $start = microtime(true);

                    try {
                        $result = $instance->handle($data, function (SettleTaskData $d) use ($next) {
                            return $next($d);
                        });
                        $duration = (int) round((microtime(true) - $start) * 1000);
                        $log->status = 'success';
                        $log->duration_ms = $duration;
                        $log->save();

                        return $result;
                    } catch (Throwable $e) {
                        $duration = (int) round((microtime(true) - $start) * 1000);
                        $log->status = 'failure';
                        $log->message = (string) $e;
                        $log->duration_ms = $duration;
                        $log->save();
                        throw $e;
                    }
                };
            })
            ->toArray();
    }
}
