<?php

namespace App\Extensions\Certificate;

use OpenSSLAsymmetricKey;

class KeyPair
{
    protected string $publicKey = '';

    protected string $privateKey = '';

    public function __construct(string $publicKey = '', string $privateKey = '')
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    public function setPublicKey(string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function setPrivateKey(string $privateKey): void
    {
        $this->privateKey = $privateKey;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getPrivateKeyResource(?string $passphrase = null): OpenSSLAsymmetricKey|false
    {
        return openssl_pkey_get_private($this->privateKey, $passphrase);
    }
}
