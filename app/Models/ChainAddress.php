<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 区块链地址模型
 */
#[Unguarded]
class ChainAddress extends Model
{
    use BelongsToTenant,
        SoftDeletes;

    /**
     * 关联网络
     *
     * @return BelongsTo
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    /**
     * 设置私钥（加密存储）
     *
     * @param  string  $value
     * @return void
     */
    public function setPrivateKeyAttribute(string $value): void
    {
        $this->attributes['private_key'] = $this->makeEncrypt($value);
    }

    /**
     * 选择性的加密
     *
     * @param  string  $data
     * @return string
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
}
