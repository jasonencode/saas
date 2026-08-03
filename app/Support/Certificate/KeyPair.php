<?php

namespace App\Support\Certificate;

use OpenSSLAsymmetricKey;

class KeyPair
{
    protected string $publicKey = '';

    protected string $privateKey = '';

    /**
     * 创建密钥对实例
     *
     * @param  string  $publicKey  公钥
     * @param  string  $privateKey  私钥
     */
    public function __construct(string $publicKey = '', string $privateKey = '')
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    /**
     * 获取公钥
     *
     * @return string 公钥
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * 设置公钥
     *
     * @param  string  $publicKey  公钥
     */
    public function setPublicKey(string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }

    /**
     * 获取私钥
     *
     * @return string 私钥
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * 设置私钥
     *
     * @param  string  $privateKey  私钥
     */
    public function setPrivateKey(string $privateKey): void
    {
        $this->privateKey = $privateKey;
    }

    /**
     * 获取私钥资源句柄
     *
     * @param  string|null  $passphrase  私钥密码
     *
     * @return OpenSSLAsymmetricKey|false 私钥资源句柄
     */
    public function getPrivateKeyResource(?string $passphrase = null): OpenSSLAsymmetricKey|false
    {
        return openssl_pkey_get_private($this->privateKey, $passphrase);
    }
}
