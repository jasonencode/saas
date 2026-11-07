<?php

namespace App\Services\Campaign;

use App\Contracts\ServiceInterface;
use App\Enums\Campaign\RedpackCodeStatus;
use App\Jobs\Campaign\SendRedpackJob;
use App\Models\Campaign\Redpack;
use App\Models\Campaign\RedpackCode;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Random\RandomException;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class RedpackService implements ServiceInterface
{
    /**
     * 批量创建红包码
     *
     * @param  Redpack  $redpack  红包活动
     * @param  int  $count  创建数量
     * @param  float  $amount  单个金额（固定模式）或最小金额（随机模式）
     * @param  string  $type  金额类型：fixed（固定）/ random（随机）
     * @param  int  $codeLength  码长度
     * @param  float|null  $maxAmount  最大金额（随机模式），为空时使用 $amount 作为最大金额
     *
     * @throws RandomException
     *
     * @return int 成功创建的数量
     */
    public function createCodesBulk(
        Redpack $redpack,
        int $count,
        float $amount,
        string $type = 'fixed',
        int $codeLength = RedpackCode::CODE_LENGTH_DEFAULT,
        ?float $maxAmount = null,
    ): int {
        if ($count <= 0) {
            return 0;
        }

        if ($count > 50000) {
            throw new InvalidArgumentException('单次最多创建 50000 个红包码');
        }

        if (!in_array($type, ['fixed', 'random'], true)) {
            throw new InvalidArgumentException('金额类型不正确');
        }

        if ($codeLength < RedpackCode::CODE_LENGTH_MIN || $codeLength > RedpackCode::CODE_LENGTH_MAX) {
            throw new InvalidArgumentException(sprintf(
                '红包码长度必须在 %d-%d 位之间',
                RedpackCode::CODE_LENGTH_MIN,
                RedpackCode::CODE_LENGTH_MAX,
            ));
        }

        $minAmount = round($amount, 2);
        $maxAmount = $maxAmount === null ? null : round($maxAmount, 2);

        if ($minAmount < 0.01) {
            throw new InvalidArgumentException('红包金额必须大于 0');
        }

        if ($type === 'random' && ($maxAmount === null || $maxAmount < $minAmount)) {
            throw new InvalidArgumentException('随机金额的最大值必须大于等于最小值');
        }

        return DB::transaction(function () use ($redpack, $count, $minAmount, $type, $codeLength, $maxAmount): int {
            $created = 0;

            for ($i = 0; $i < $count; $i++) {
                $codeAmount = $type === 'random'
                    ? $this->randomAmount($minAmount, $maxAmount ?? $minAmount)
                    : $minAmount;

                $redpack->codes()->create([
                    'code' => $this->generateCode($codeLength),
                    'amount' => $codeAmount,
                    'status' => RedpackCodeStatus::Active,
                ]);

                $created++;
            }

            return $created;
        });
    }

    /**
     * 生成唯一红包码
     *
     * @param  int  $length  码长度
     *
     * @return string 红包码
     */
    protected function generateCode(int $length = RedpackCode::CODE_LENGTH_DEFAULT): string
    {
        do {
            $code = Str::random($length);
        } while (RedpackCode::where('code', $code)->exists());

        return $code;
    }

    /**
     * 生成随机金额
     *
     * @param  float  $minAmount  最小金额
     * @param  float  $maxAmount  最大金额
     *
     * @throws RandomException
     *
     * @return float 随机金额（保留两位小数）
     */
    protected function randomAmount(float $minAmount, float $maxAmount): float
    {
        $min = max(0.3, $minAmount);
        $max = max($min, $maxAmount);

        return round(random_int(($min * 100), ($max * 100)) / 100, 2);
    }

    /**
     * 领取红包码
     *
     * @param  RedpackCode  $code  红包码
     * @param  User  $user  领取用户
     * @param  string|null  $ip  用户 IP
     *
     * @throws InvalidArgumentException 活动不可用或红包码无效
     */
    public function claim(RedpackCode $code, User $user, ?string $ip = null): RedpackCode
    {
        $claimed = DB::transaction(function () use ($code, $user, $ip): RedpackCode {
            $redpack = Redpack::query()
                ->whereKey($code->redpack_id)
                ->lockForUpdate()
                ->first();

            if ($redpack === null || !$redpack->isActive()) {
                throw new InvalidArgumentException('红包活动已结束或已禁用');
            }

            if ($redpack->limit_per_user !== null && $redpack->limit_per_user > 0) {
                $claimedCount = RedpackCode::query()
                    ->where('redpack_id', $redpack->getKey())
                    ->where('user_id', $user->getKey())
                    ->whereIn('status', [
                        RedpackCodeStatus::Claimed->value,
                        RedpackCodeStatus::Sending->value,
                        RedpackCodeStatus::Sent->value,
                    ])
                    ->count();

                if ($claimedCount >= $redpack->limit_per_user) {
                    throw new InvalidArgumentException('已达该活动的领取上限');
                }
            }

            // 条件更新保证并发下只有一个请求能领取成功
            $affected = RedpackCode::query()
                ->whereKey($code->getKey())
                ->where('status', RedpackCodeStatus::Active->value)
                ->update([
                    'user_id' => $user->getKey(),
                    'status' => RedpackCodeStatus::Claimed->value,
                    'claimed_at' => now(),
                    'claimed_ip' => $ip,
                ]);

            if ($affected === 0) {
                throw new InvalidArgumentException('红包码无效或已被领取');
            }

            return $code->refresh();
        });

        SendRedpackJob::dispatch($claimed);

        return $claimed;
    }

    /**
     * 将红包码导出为 ZIP 文件（包含 CSV 列表）
     *
     * @throws \RuntimeException 压缩失败
     *
     * @return Response ZIP 文件响应
     */
    public function exportCodesToZip(Redpack $redpack): Response
    {
        $redpackName = preg_replace('/[^\p{Han}A-Za-z0-9_\-]+/u', '_', $redpack->name) ?: 'redpack';
        $fileName = "红包码_{$redpackName}_".date('YmdHis');
        $csvFileName = "$fileName.csv";
        $zipFileName = "$fileName.zip";

        $csvPath = tempnam(sys_get_temp_dir(), 'redpack_csv');
        $zipPath = tempnam(sys_get_temp_dir(), 'redpack_zip');
        $handle = fopen($csvPath, 'wb+');

        // 添加 BOM 以防止 Excel 打开 CSV 时乱码
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($handle, ['红包码', '金额(元)', '状态', '领取人', '领取时间']);

        $redpack->codes()
            ->with('user')
            ->chunk(1000, function (Collection $codes) use ($handle) {
                foreach ($codes as $code) {
                    fputcsv($handle, [
                        $this->sanitizeCsvCell($code->code),
                        number_format($code->amount, 2),
                        $code->status->getLabel(),
                        $this->sanitizeCsvCell($code->user?->name ?? '-'),
                        $code->claimed_at?->format('Y-m-d H:i:s') ?? '-',
                    ]);
                }
            });

        fclose($handle);

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($csvPath);

            throw new \RuntimeException("无法创建 ZIP 文件 [{$zipFileName}]");
        }

        $zip->addFile($csvPath, $csvFileName);
        $zip->close();

        $response = response()->download($zipPath, $zipFileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();

        // 删除临时文件
        @unlink($csvPath);

        return $response;
    }

    /**
     * 防止 CSV 公式注入
     */
    private function sanitizeCsvCell(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return preg_match('/^[=+\-@]/', ltrim($value)) === 1 ? "'".$value : $value;
    }
}
