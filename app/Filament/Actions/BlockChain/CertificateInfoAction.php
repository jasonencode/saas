<?php

namespace App\Filament\Actions\BlockChain;

use App\Models\Certificate;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;

class CertificateInfoAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'certificateInfo';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('证书信息');
        $this->modalHeading('证书信息');
        $this->modalSubmitAction(false);
        $this->slideOver();
        $this->visible(fn(Certificate $certificate) => $certificate->isEnabled());

        $this->fillForm(fn(Certificate $record) => openssl_x509_parse($record->certificate));

        $this->schema([
            TextInput::make('subject.CN')
                ->label('证书主题')
                ->readOnly(),
            TextInput::make('issuer.CN')
                ->label('签发机构')
                ->readOnly(),
            TextInput::make('serialNumberHex')
                ->label('证书序列号')
                ->readOnly(),
            TextInput::make('signatureTypeSN')
                ->label('签名算法')
                ->readOnly(),
            DateTimePicker::make('validFrom_time_t')
                ->label('有效期始')
                ->readOnly(),
            DateTimePicker::make('validTo_time_t')
                ->label('有效期止')
                ->readOnly(),
            TextInput::make('version')
                ->label('版本')
                ->readOnly(),
        ]);
    }
}
