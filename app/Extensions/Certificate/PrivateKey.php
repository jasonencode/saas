<?php

namespace App\Extensions\Certificate;

use Exception;
use OpenSSLAsymmetricKey;
use RuntimeException;

class PrivateKey
{
    /**
     * 私钥实例，单例模式
     *
     * @var PrivateKey|null
     */
    protected static ?PrivateKey $instance = null;

    /**
     * 私钥句柄
     *
     * @var OpenSSLAsymmetricKey
     */
    protected OpenSSLAsymmetricKey $privateKey;

    /**
     * 配置
     *
     * @var array
     */
    protected array $options = [];

    /**
     * 密钥类型
     *
     * @var string
     */
    protected string $type = 'rsa';

    /**
     * 私钥密码
     *
     * @var string|null
     */
    protected ?string $password = null;

    public function __construct(array $options)
    {
        $this->options = $options;
        $privateKey = openssl_pkey_new($options);
        if (!$privateKey) {
            throw new RuntimeException('生成私钥失败');
        }
        $this->privateKey = $privateKey;
        $this->type = match (openssl_pkey_get_details($this->privateKey)['type']) {
            OPENSSL_KEYTYPE_RSA => OPENSSL_KEYTYPE_RSA,
            OPENSSL_KEYTYPE_EC => OPENSSL_KEYTYPE_EC,
            default => 'unknown',
        };
    }

    public static function makeRsaKey(int $length = 4096, string $digestAlg = 'sha256'): static
    {
        if (self::$instance) {
            return self::$instance;
        }

        if (!in_array($length, [1024, 2048, 4096])) {
            throw new RuntimeException('密钥长度错误');
        }
        if (!in_array($digestAlg, ['md5', 'sha1', 'sha224', 'sha256', 'sha384', 'sha512'])) {
            throw new RuntimeException('摘要算法错误');
        }

        $options = [
            'private_key_bits' => $length,       // 密钥长度
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'digest_alg' => $digestAlg,
        ];

        return self::$instance = new self($options);
    }

    public static function makeEcKey(int $length = 384, string $digestAlg = 'sha256'): static
    {
        if (self::$instance) {
            return self::$instance;
        }

        if (!in_array($length, [256, 384, 512])) {
            throw new RuntimeException('密钥长度错误');
        }
        if (!in_array($digestAlg, ['md5', 'sha1', 'sha224', 'sha256', 'sha384', 'sha512'])) {
            throw new RuntimeException('摘要算法错误');
        }

        $curveName = match ($length) {
            256 => 'prime256v1',
            384 => 'secp384r1',
            512 => 'secp521r1',
        };

        $options = [
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => $curveName,
            'digest_alg' => $digestAlg,
        ];

        return self::$instance = new self($options);
    }

    public function password(?string $password = null): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPrivateKey(): OpenSSLAsymmetricKey
    {
        return $this->privateKey;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @throws Exception
     */
    public function export(): KeyPair
    {
        if (openssl_pkey_export($this->privateKey, $privateKeyPem, $this->password, $this->options)) {
            $publicKey = openssl_pkey_get_details($this->privateKey);

            return new KeyPair($publicKey['key'], $privateKeyPem);
        }

        throw new RuntimeException('导出私钥失败');
    }
}
