<?php

namespace App\Services\Finance;

use App\Contracts\ServiceInterface;
use App\Contracts\SettleTaskData;
use App\Enums\Finance\VoucherStatus;
use App\Models\Finance\Task;
use App\Models\Finance\Voucher;
use App\Models\Finance\VoucherLog;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Pipeline;
use InvalidArgumentException;
use Throwable;

class SettlementService implements ServiceInterface
{
    /**
     * 执行中状态的最长持有时间（分钟）
     *
     * 超过该时长仍停留在「执行中」，视为进程异常退出，允许重新抢占。
     */
    protected const int PROCESSING_TIMEOUT_MINUTES = 60;

    /**
     * 执行结算
     *
     * @param  Voucher  $voucher  结算凭据
     *
     * @throws Throwable 结算过程中发生异常
     *
     * @return bool 是否执行成功
     */
    public function execute(Voucher $voucher): bool
    {
        if ($voucher->status === VoucherStatus::Success) {
            throw new InvalidArgumentException('该凭据已经结算完成，请勿重复操作');
        }

        // 条件更新抢占：仅「待执行」「失败」或超时未收尾的「执行中」可以被接管
        $affected = Voucher::where('id', $voucher->getKey())
            ->where(static function (Builder $query): void {
                $query->whereNotIn('status', [VoucherStatus::Processing, VoucherStatus::Success])
                    ->orWhere(static function (Builder $query): void {
                        $query->where('status', VoucherStatus::Processing)
                            ->where('updated_at', '<=', now()->subMinutes(self::PROCESSING_TIMEOUT_MINUTES));
                    });
            })
            ->update(['status' => VoucherStatus::Processing]);

        if ($affected === 0) {
            throw new InvalidArgumentException('该凭据正在结算中或已完成，请勿重复操作');
        }

        $voucher->refresh();

        $start = microtime(true);

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

            // 事务内的步骤日志已随回滚丢失，失败日志必须在回滚后单独落库
            $duration = (int) round((microtime(true) - $start) * 1000);

            $voucher->status = VoucherStatus::Failure;
            $voucher->exception = (string) $exception;
            $voucher->save();

            VoucherLog::create([
                'voucher_id' => $voucher->getKey(),
                'step' => '结算',
                'status' => 'failure',
                'message' => (string) $exception,
                'duration_ms' => $duration,
            ]);

            throw $exception;
        }
    }

    /**
     * 获取凭据任务
     *
     * @param  Voucher  $voucher  结算凭据
     *
     * @throws InvalidArgumentException 任务服务类不存在
     *
     * @return array 凭据任务列表
     */
    protected function getVoucherTasks(Voucher $voucher): array
    {
        return $voucher->plan
            ->tasks()
            ->ofEnabled()
            ->orderByDesc('sort')
            ->get()
            ->map(function (Task $task) {
                if (!class_exists($task->service)) {
                    throw new InvalidArgumentException(sprintf('结算任务 [%s#%s] 的服务类不存在：%s', $task->name, $task->getKey(), $task->service));
                }

                $instance = TaskService::resolve($task->service, $task);

                return static function (SettleTaskData $data, Closure $next) use ($task, $instance) {
                    $log = VoucherLog::create([
                        'voucher_id' => $data->voucher->getKey(),
                        'task_id' => $task->getKey(),
                        'step' => $instance->getTitle(),
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
            ->values()
            ->all();
    }
}
