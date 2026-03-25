<?php

namespace App\Services;

use App\Contracts\ServiceInterface;
use App\Models\Redpack;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;
use Symfony\Component\HttpFoundation\Response;

class RedpackService implements ServiceInterface
{
    /**
     * 将红包码导出为 ZIP 文件（包含 CSV 列表）
     *
     * @throws ZipException
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
            ->chunk(1000, function ($codes) use ($handle) {
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
