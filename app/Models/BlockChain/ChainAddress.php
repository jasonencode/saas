<?php

namespace App\Models\BlockChain;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Policies\BlockChain\ChainAddressPolicy;
use App\Support\RSA\RSA;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

#[Unguarded]
#[UsePolicy(ChainAddressPolicy::class)]
class ChainAddress extends Model
{
    use BelongsToTenant,
        SoftDeletes;

    /**
     * 关联网络
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    /**
     * 设置私钥（加密存储）
     */
    public function setPrivateKeyAttribute(string $value): void
    {
        $this->attributes['private_key'] = $this->makeEncrypt($value);
    }

    /**
     * 选择性的加密
     */
    protected function makeEncrypt(string $data): string
    {
        $publicKey = config('custom.block_chain.public_key');

        if ($publicKey) {
            openssl_public_encrypt($data, $encrypted, $publicKey);

            return base64_encode($encrypted);
        }

        return $data;
    }

    /**
     * 获取解密后的私钥
     *
     *
     * @throws RuntimeException 私钥为空或解密失败
     *
     * @return string 解密后的私钥内容
     */
    public function getDecryptedPrivateKey(): string
    {
        $privateKey = (string) ($this->getAttribute('private_key') ?? '');

        if ($privateKey === '') {
            throw new RuntimeException('区块链地址缺少私钥。');
        }

        $decryptKey = config('custom.block_chain.private_key');

        if (blank($decryptKey)) {
            return $privateKey;
        }

        try {
            return new RSA(privateKeyPem: $decryptKey)->decrypt($privateKey, OPENSSL_PKCS1_PADDING);
        } catch (RuntimeException $exception) {
            if (preg_match('/^(0x)?[a-fA-F0-9]{64}$/', $privateKey) === 1) {
                return $privateKey;
            }

            throw $exception;
        }
    }
}
