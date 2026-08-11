<?php

namespace App\Filament\Actions\BlockChain;

use App\Enums\BlockChain\CertificateType;
use App\Models\BlockChain\Certificate;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use PhpZip\ZipFile;
use Symfony\Component\HttpFoundation\Response;

class CertificateDownloadAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'certificateDownload';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('下载证书');
        $this->icon(Heroicon::OutlinedArrowDownTray);

        $this->visible(fn (Certificate $certificate): bool => userCan(self::getDefaultName(), $certificate) && $this->isDownloadable($certificate));

        $this->action(function (Certificate $key): Response {
            $zipFile = new ZipFile;
            $zipFile->addFromString('private.key', $key->private_key);
            $zipFile->addFromString('public.pem', $key->certificate);
            $fileName = sprintf('用户证书-%s.%s', $key->created_at->format('Y-m-d-H-i-s'), 'zip');

            return $zipFile->outputAsSymfonyResponse($fileName);
        });
    }

    protected function isDownloadable(Certificate $certificate): bool
    {
        return $certificate->type === CertificateType::Certificate && $certificate->isEnabled();
    }
}
