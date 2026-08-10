<?php

namespace App\Models\Foundation;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Policies\Foundation\WechatPaymentPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(WechatPaymentPolicy::class)]
class WechatPayment extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

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
        return $this->belongsTo(Wechat::class)
            ->withTrashed();
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

        return [
            '_force' => true,
            'wechat' => [
                'default' => [
                    'mch_id' => $this->mch_id,
                    'mch_key_v3' => $this->secret,
                    'mch_secret_cert' => $this->private_key,
                    'mch_public_cert_path' => $publicCertPath,
                    'mp_app_id' => $this->wechat?->app_id ?? '',
                ],
            ],
        ];
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
