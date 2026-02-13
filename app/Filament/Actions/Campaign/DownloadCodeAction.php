<?php

namespace App\Filament\Actions\Campaign;

use App\Models\Redpack;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use PhpZip\ZipFile;

class DownloadCodeAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'downloadCode';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('下载红包码');
        $this->icon(Heroicon::OutlinedInboxArrowDown);
        $this->action(function (Redpack $record) {
            $redpackName = $record->name;
            $fileName = "红包码_{$redpackName}_".date('YmdHis');
            $csvFileName = "$fileName.csv";
            $zipFileName = "$fileName.zip";
            $csvPath = tempnam(sys_get_temp_dir(), 'redpack_csv');
            $handle = fopen($csvPath, 'wb+');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['红包码', '金额(元)', '状态', '领取人', '领取时间']);

            $record->codes()
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

            return new ZipFile()
                ->addFile($csvPath, $csvFileName)
                ->outputAsSymfonyResponse($zipFileName);
        });
    }
}
