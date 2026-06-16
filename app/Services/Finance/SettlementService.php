<?php

namespace App\Services\Finance;

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
use Throwable;

class SettlementService implements ServiceInterface
{
    /**
     * 执行结算
     *
     * @throws Throwable
     */
    public function execute(Voucher $voucher): bool
    {
        if ($voucher->status === VoucherStatus::Success) {
            throw new InvalidArgumentException('该凭据已经结算完成，请勿重复操作');
        }

        $affected = Voucher::where('id', $voucher->getKey())
            ->where('status', '!=', VoucherStatus::Processing)
            ->where('status', '!=', VoucherStatus::Success)
            ->update(['status' => VoucherStatus::Processing]);

        if ($affected === 0) {
            throw new InvalidArgumentException('该凭据正在结算中或已完成，请勿重复操作');
        }

        $voucher->refresh();

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
                if (!class_exists($task->service)) {
                    return null;
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
            ->toArray();
    }
}
