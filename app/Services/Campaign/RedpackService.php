<?php

namespace App\Services\Campaign;

use App\Contracts\ServiceInterface;
use App\Enums\Campaign\RedpackCodeStatus;
use App\Models\Campaign\Redpack;
use App\Models\Campaign\RedpackCode;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;
use Random\RandomException;
use Symfony\Component\HttpFoundation\Response;

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
        $created = 0;

        for ($i = 0; $i < $count; $i++) {
            $codeAmount = $type === 'random'
                ? $this->randomAmount($amount, $maxAmount ?? $amount)
                : $amount;

            $code = $this->generateCode($codeLength);

            $redpack->codes()->create([
                'code' => $code,
                'amount' => $codeAmount,
                'status' => RedpackCodeStatus::Active,
            ]);
            $created++;
        }

        return $created;
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
    public function claim(RedpackCode $code, User $user, ?string $ip = null): void
    {
        $redpack = $code->redpack;

        if (!$redpack->isActive()) {
            throw new InvalidArgumentException('红包活动已结束或已禁用');
        }

        if (!$code->isClaimable()) {
            throw new InvalidArgumentException('红包码无效或已被领取');
        }

        $code->update([
            'user_id' => $user->getKey(),
            'status' => RedpackCodeStatus::Claimed,
            'claimed_at' => now(),
            'claimed_ip' => $ip,
        ]);
    }

    /**
     * 将红包码导出为 ZIP 文件（包含 CSV 列表）
     *
     * @param  Redpack  $redpack  红包活动
     *
     * @throws ZipException 压缩失败
     *
     * @return Response ZIP 文件响应
     */
    public function exportCodesToZip(Redpack $redpack): Response
    {
        $redpackName = $redpack->name;
        $fileName = "红包码_{$redpackName}_".date('YmdHis');
        $csvFileName = "$fileName.csv";
        $zipFileName = "$fileName.zip";

        $csvPath = tempnam(sys_get_temp_dir(), 'redpack_csv');
        $handle = fopen($csvPath, 'wb+');

        // 添加 BOM 以防止 Excel 打开 CSV 时乱码
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($handle, ['红包码', '金额(元)', '状态', '领取人', '领取时间']);

        $redpack->codes()
            ->with('user')
            ->chunk(1000, function (Collection $codes) use ($handle) {
                foreach ($codes as $code) {
                    fputcsv($handle, [
                        $code->code,
                        number_format($code->amount, 2),
                        $code->status->getLabel(),
                        $code->user?->name ?? '-',
                        $code->claimed_at?->format('Y-m-d H:i:s') ?? '-',
                    ]);
                }
            });

        fclose($handle);

        $response = (new ZipFile)
            ->addFile($csvPath, $csvFileName)
            ->outputAsSymfonyResponse($zipFileName);

        // 删除临时文件
        @unlink($csvPath);

        return $response;
    }
}
