<?php

namespace App\Extensions\RSA;

use OpenSSLAsymmetricKey;
use RuntimeException;

class RSA
{
    private ?string $privateKeyPem;

    private ?string $publicKeyPem;

    private ?string $passphrase;

    public function __construct(?string $privateKeyPem = null, ?string $publicKeyPem = null, ?string $passphrase = null)
    {
        $this->privateKeyPem = $privateKeyPem;
        $this->publicKeyPem = $publicKeyPem;
        $this->passphrase = $passphrase;
    }

    public static function fromKeyFiles(
        ?string $privateKeyPath = null,
        ?string $publicKeyPath = null,
        ?string $passphrase = null
    ): self {
        $private = null;
        $public = null;
        if ($privateKeyPath) {
            if (!is_file($privateKeyPath)) {
                throw new RuntimeException("私钥文件不存在: $privateKeyPath");
            }
            $private = file_get_contents($privateKeyPath);
        }
        if ($publicKeyPath) {
            if (!is_file($publicKeyPath)) {
                throw new RuntimeException("公钥文件不存在: $publicKeyPath");
            }
            $public = file_get_contents($publicKeyPath);
        }

        return new self($private, $public, $passphrase);
    }

    /**
     * 生成 RSA 密钥对（PEM 格式）。
     *
     * @return array{privateKey:string, publicKey:string}
     */
    public static function generateKeyPair(int $bits = 2048, ?string $passphrase = null): array
    {
        $config = [
            'private_key_bits' => $bits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $res = openssl_pkey_new($config);
        if ($res === false) {
            throw new RuntimeException('密钥生成失败: '.(openssl_error_string() ?: 'unknown'));
        }

        $privateKey = '';
        $exportOk = openssl_pkey_export($res, $privateKey, $passphrase ?: null);
        if ($exportOk === false) {
            throw new RuntimeException('私钥导出失败: '.(openssl_error_string() ?: 'unknown'));
        }
        $details = openssl_pkey_get_details($res);
        if ($details === false || empty($details['key'])) {
            throw new RuntimeException('公钥导出失败: '.(openssl_error_string() ?: 'unknown'));
        }
        $publicKey = $details['key'];

        return [
            'privateKey' => $privateKey,
            'publicKey' => $publicKey,
        ];
    }

    public function setPrivateKeyByPath(string $path, ?string $passphrase = null): self
    {
        if (!is_file($path)) {
            throw new RuntimeException("私钥文件不存在: $path");
        }
        $this->privateKeyPem = file_get_contents($path);
        $this->passphrase = $passphrase;

        return $this;
    }

    public function setPublicKeyByPath(string $path): self
    {
        if (!is_file($path)) {
            throw new RuntimeException("公钥文件不存在: $path");
        }
        $this->publicKeyPem = file_get_contents($path);

        return $this;
    }

    /**
     * 公钥加密（支持长文本分块），返回 Base64 文本。
     */
    public function encrypt(string $data, int $padding = OPENSSL_PKCS1_PADDING): string
    {
        $pub = $this->getOpenSslPublicKey();
        [$maxChunk, $blockSize] = $this->chunkSizes($padding, $pub);
        $parts = [];
        $offset = 0;
        $len = strlen($data);
        while ($offset < $len) {
            $chunk = substr($data, $offset, $maxChunk);
            $encrypted = '';
            $ok = openssl_public_encrypt($chunk, $encrypted, $pub, $padding);
            if ($ok === false) {
                throw new RuntimeException('加密失败: '.(openssl_error_string() ?: 'unknown'));
            }
            $parts[] = $encrypted;
            $offset += $maxChunk;
        }

        return base64_encode(implode('', $parts));
    }

    private function getOpenSslPublicKey(): OpenSSLAsymmetricKey
    {
        if (!$this->publicKeyPem) {
            throw new RuntimeException('未设置公钥');
        }
        $key = openssl_pkey_get_public($this->publicKeyPem);
        if ($key === false) {
            throw new RuntimeException('加载公钥失败: '.(openssl_error_string() ?: 'unknown'));
        }

        return $key;
    }

    /**
     * 计算分块大小（PKCS1: 明文最大 = keyBytes - 11，密文块大小 = keyBytes）。
     *
     * @return array{0:int,1:int} [maxPlainChunk, cipherBlockSize]
     */
    private function chunkSizes(int $padding, $opensslKey): array
    {
        $details = openssl_pkey_get_details($opensslKey);
        if ($details === false || empty($details['bits'])) {
            throw new RuntimeException('无法获取密钥详情以计算分块大小');
        }
        $keyBytes = intdiv((int) $details['bits'], 8);
        $maxPlain = ($padding === OPENSSL_PKCS1_PADDING) ? ($keyBytes - 11) : $keyBytes;

        return [$maxPlain, $keyBytes];
    }

    /**
     * 私钥解密（支持分块），参数为 Base64 文本。
     */
    public function decrypt(string $payload, int $padding = OPENSSL_PKCS1_PADDING): string
    {
        $priv = $this->getOpenSslPrivateKey();
        $cipher = base64_decode($payload, true);
        if ($cipher === false) {
            throw new RuntimeException('密文不是有效的Base64字符串');
        }
        [$maxChunk, $blockSize] = $this->chunkSizes($padding, $priv);
        $parts = [];
        $offset = 0;
        $len = strlen($cipher);
        while ($offset < $len) {
            $block = substr($cipher, $offset, $blockSize);
            $decrypted = '';
            $ok = openssl_private_decrypt($block, $decrypted, $priv, $padding);
            if ($ok === false) {
                throw new RuntimeException('解密失败: '.(openssl_error_string() ?: 'unknown'));
            }
            $parts[] = $decrypted;
            $offset += $blockSize;
        }

        return implode('', $parts);
    }

    private function getOpenSslPrivateKey(): OpenSSLAsymmetricKey
    {
        if (!$this->privateKeyPem) {
            throw new RuntimeException('未设置私钥');
        }
        $key = openssl_pkey_get_private($this->privateKeyPem, $this->passphrase ?? '');
        if ($key === false) {
            throw new RuntimeException('加载私钥失败: '.(openssl_error_string() ?: 'unknown'));
        }

        return $key;
    }

    /**
     * 私钥签名，返回 Base64 签名。
     */
    public function sign(string $data, int $algo = OPENSSL_ALGO_SHA256): string
    {
        $priv = $this->getOpenSslPrivateKey();
        $signature = '';
        $ok = openssl_sign($data, $signature, $priv, $algo);
        if ($ok === false) {
            throw new RuntimeException('签名失败: '.(openssl_error_string() ?: 'unknown'));
        }

        return base64_encode($signature);
    }

    /**
     * 公钥验签。
     */
    public function verify(string $data, string $signatureBase64, int $algo = OPENSSL_ALGO_SHA256): bool
    {
        $pub = $this->getOpenSslPublicKey();
        $sig = base64_decode($signatureBase64, true);
        if ($sig === false) {
            return false;
        }
        $result = openssl_verify($data, $sig, $pub, $algo);

        return $result === 1;
    }
}
