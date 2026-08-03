<?php

namespace App\Services\Campaign;

use App\Contracts\ServiceInterface;
use App\Enums\Campaign\RedpackCodeStatus;
use App\Models\Campaign\Redpack;
use App\Models\Campaign\RedpackCode;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RedpackService implements ServiceInterface
{
    /**
     * 批量创建红包码
     *
     * @param  Redpack  $redpack  红包活动
     * @param  int  $count  创建数量
     * @param  float  $amount  单个金额
     *
     * @return int 成功创建的数量
     */
    public function createCodesBulk(Redpack $redpack, int $count, float $amount): int
    {
        $created = 0;

        for ($i = 0; $i < $count; $i++) {
            $redpack->codes()->create([
                'amount' => $amount,
                'status' => RedpackCodeStatus::Active,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * 领取红包码
     *
     * @param  RedpackCode  $code  红包码
     * @param  User  $user  领取用户
     * @param  string|null  $ip  用户 IP
     *
     * @throws InvalidArgumentException 活动不可用或红包码无效
     * @throws Throwable 事务异常
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

        DB::transaction(static function () use ($code, $user, $ip) {
            $code->claim($user, $ip);
        });
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
