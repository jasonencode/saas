<?php

namespace App\Models\Foundation;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Policies\Foundation\WechatPaymentPolicy;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Hidden(['secret', 'private_key', 'platform_cert'])]
#[Unguarded]
#[UsePolicy(WechatPaymentPolicy::class)]
class WechatPayment extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'private_key' => 'encrypted',
            'platform_cert' => 'encrypted',
        ];
    }

    /**
     * 临时证书文件路径（用于清理）
     *
     * @var string[]
     */
    public array $tempCertFiles = [];

    /**
     * 关联微信配置
     *
     * @return BelongsTo<Wechat>
     */
    public function wechat(): BelongsTo
    {
        return $this->belongsTo(Wechat::class);
    }

    /**
     * 构建 yansongda/pay 配置数组
     *
     * 若 public_key 为 PEM 内容而非文件路径，会写入临时文件。
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $publicCertPath = $this->resolveCertPath($this->public_key);

        if ($publicCertPath !== $this->public_key) {
            $this->tempCertFiles[] = $publicCertPath;
        }

        $config = [
            '_force' => true,
            'wechat' => [
                'default' => [
                    'mch_id' => $this->mch_id,
                    'mch_secret_key' => $this->secret,
                    'mch_secret_cert' => $this->private_key,
                    'mch_public_cert_path' => $publicCertPath,
                    'mp_app_id' => $this->wechat?->app_id ?? '',
                ],
            ],
        ];

        $platformCertPath = $this->resolveCertPath($this->platform_cert);

        if ($platformCertPath !== $this->platform_cert) {
            $this->tempCertFiles[] = $platformCertPath;
        }

        $serial = $this->certificateSerial($platformCertPath);

        if ($serial) {
            $config['wechat']['default']['wechat_public_cert_path'] = [$serial => $platformCertPath];
        }

        return $config;
    }

    /**
     * 读取证书序列号（微信回调验签按序列号匹配平台证书）
     *
     * @param  string|null  $path  证书路径
     *
     * @return string|null 大写十六进制序列号
     */
    private function certificateSerial(?string $path): ?string
    {
        if (empty($path) || !is_file($path)) {
            return null;
        }

        $certificate = openssl_x509_read((string) file_get_contents($path));

        if ($certificate === false) {
            return null;
        }

        $parsed = openssl_x509_parse($certificate);

        return empty($parsed['serialNumberHex']) ? null : strtoupper($parsed['serialNumberHex']);
    }

    /**
     * 若为 PEM 内容则写入临时文件，否则原样返回路径
     */
    private function resolveCertPath(?string $cert): ?string
    {
        if (empty($cert) || is_file($cert)) {
            return $cert;
        }

        $path = tempnam(sys_get_temp_dir(), 'wx_cert_');
        file_put_contents($path, $cert);

        return $path;
    }

    /**
     * 清理临时证书文件
     */
    public function cleanupTempFiles(): void
    {
        foreach ($this->tempCertFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        $this->tempCertFiles = [];
    }
}
