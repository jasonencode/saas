<?php

namespace App\Support\RSA;

use OpenSSLAsymmetricKey;
use RuntimeException;

class RSA
{
    private ?string $privateKeyPem;

    private ?string $publicKeyPem;

    private ?string $passphrase;

    /**
     * 创建 RSA 实例
     *
     * @param  string|null  $privateKeyPem  私钥 PEM 内容
     * @param  string|null  $publicKeyPem  公钥 PEM 内容
     * @param  string|null  $passphrase  私钥密码
     */
    public function __construct(?string $privateKeyPem = null, ?string $publicKeyPem = null, ?string $passphrase = null)
    {
        $this->privateKeyPem = $privateKeyPem;
        $this->publicKeyPem = $publicKeyPem;
        $this->passphrase = $passphrase;
    }

    /**
     * 从文件创建 RSA 实例
     *
     * @param  string|null  $privateKeyPath  私钥文件路径
     * @param  string|null  $publicKeyPath  公钥文件路径
     * @param  string|null  $passphrase  私钥密码
     *
     * @throws RuntimeException 文件不存在
     *
     * @return self RSA 实例
     */
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
     * 生成 RSA 密钥对（PEM 格式）
     *
     * @param  int  $bits  密钥长度
     * @param  string|null  $passphrase  私钥密码
     *
     * @throws RuntimeException 密钥生成失败
     *
     * @return array{privateKey: string, publicKey: string} 密钥对
     */
    public static function generateKeyPair(int $bits = 2048, ?string $passphrase = null): array
    {
        $config = [
            'private_key_bits' => $bits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $res = openssl_pkey_new($config);
        if ($res === false) {
            throw new RuntimeException('密钥生成失败');
        }

        $privateKey = '';
        $exportOk = openssl_pkey_export($res, $privateKey, $passphrase ?: null);
        if ($exportOk === false) {
            throw new RuntimeException('私钥导出失败');
        }
        $details = openssl_pkey_get_details($res);
        if ($details === false || empty($details['key'])) {
            throw new RuntimeException('公钥导出失败');
        }
        $publicKey = $details['key'];

        return [
            'privateKey' => $privateKey,
            'publicKey' => $publicKey,
        ];
    }

    /**
     * 通过文件路径设置私钥
     *
     * @param  string  $path  私钥文件路径
     * @param  string|null  $passphrase  私钥密码
     *
     * @throws RuntimeException 文件不存在
     *
     * @return self RSA 实例
     */
    public function setPrivateKeyByPath(string $path, ?string $passphrase = null): self
    {
        if (!is_file($path)) {
            throw new RuntimeException("私钥文件不存在: $path");
        }
        $this->privateKeyPem = file_get_contents($path);
        $this->passphrase = $passphrase;

        return $this;
    }

    /**
     * 通过文件路径设置公钥
     *
     * @param  string  $path  公钥文件路径
     *
     * @throws RuntimeException 文件不存在
     *
     * @return self RSA 实例
     */
    public function setPublicKeyByPath(string $path): self
    {
        if (!is_file($path)) {
            throw new RuntimeException("公钥文件不存在: $path");
        }
        $this->publicKeyPem = file_get_contents($path);

        return $this;
    }

    /**
     * 公钥加密（支持长文本分块）
     *
     * @param  string  $data  明文数据
     * @param  int  $padding  填充方式
     *
     * @throws RuntimeException 加密失败
     *
     * @return string Base64 编码的密文
     */
    public function encrypt(string $data, int $padding = OPENSSL_PKCS1_OAEP_PADDING): string
    {
        if ($data === '') {
            return '';
        }

        $pub = $this->getOpenSslPublicKey();
        [$maxChunk] = $this->chunkSizes($padding, $pub);
        $parts = [];
        $offset = 0;
        $len = strlen($data);
        while ($offset < $len) {
            $chunk = substr($data, $offset, $maxChunk);
            $encrypted = '';
            $ok = openssl_public_encrypt($chunk, $encrypted, $pub, $padding);
            if ($ok === false) {
                throw new RuntimeException('加密失败');
            }
            $parts[] = $encrypted;
            $offset += $maxChunk;
        }

        return base64_encode(implode('', $parts));
    }

    /**
     * 私钥解密（支持分块）
     *
     * @param  string  $payload  Base64 编码的密文
     * @param  int  $padding  填充方式
     *
     * @throws RuntimeException 解密失败
     *
     * @return string 明文数据
     */
    public function decrypt(string $payload, int $padding = OPENSSL_PKCS1_OAEP_PADDING): string
    {
        if ($payload === '') {
            return '';
        }

        $priv = $this->getOpenSslPrivateKey();
        $cipher = base64_decode($payload, true);
        if ($cipher === false) {
            throw new RuntimeException('密文不是有效的Base64字符串');
        }
        [, $blockSize] = $this->chunkSizes($padding, $priv);
        $parts = [];
        $offset = 0;
        $len = strlen($cipher);
        while ($offset < $len) {
            $block = substr($cipher, $offset, $blockSize);
            $decrypted = '';
            $ok = openssl_private_decrypt($block, $decrypted, $priv, $padding);
            if ($ok === false) {
                throw new RuntimeException('解密失败');
            }
            $parts[] = $decrypted;
            $offset += $blockSize;
        }

        return implode('', $parts);
    }

    /**
     * 私钥签名
     *
     * @param  string  $data  待签名数据
     * @param  int  $algo  签名算法
     *
     * @throws RuntimeException 签名失败
     *
     * @return string Base64 编码的签名
     */
    public function sign(string $data, int $algo = OPENSSL_ALGO_SHA256): string
    {
        $priv = $this->getOpenSslPrivateKey();
        $signature = '';
        $ok = openssl_sign($data, $signature, $priv, $algo);
        if ($ok === false) {
            throw new RuntimeException('签名失败');
        }

        return base64_encode($signature);
    }

    /**
     * 公钥验签
     *
     * @param  string  $data  原始数据
     * @param  string  $signatureBase64  Base64 编码的签名
     * @param  int  $algo  签名算法
     *
     * @return bool 验签是否成功
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

    /**
     * 清理内存中的敏感密钥数据
     */
    public function destroy(): void
    {
        $this->privateKeyPem = null;
        $this->publicKeyPem = null;
        $this->passphrase = null;
    }

    /**
     * 获取 OpenSSL 公钥句柄
     *
     *
     * @throws RuntimeException 未设置公钥或加载失败
     *
     * @return OpenSSLAsymmetricKey 公钥句柄
     */
    private function getOpenSslPublicKey(): OpenSSLAsymmetricKey
    {
        if (!$this->publicKeyPem) {
            throw new RuntimeException('未设置公钥');
        }
        $key = openssl_pkey_get_public($this->publicKeyPem);
        if ($key === false) {
            throw new RuntimeException('加载公钥失败');
        }

        return $key;
    }

    /**
     * 计算分块大小
     *
     * @param  int  $padding  填充方式
     * @param  OpenSSLAsymmetricKey  $opensslKey  密钥句柄
     *
     * @throws RuntimeException 无法获取密钥详情
     *
     * @return array{0: int, 1: int} [最大明文块大小, 密文块大小]
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
     * 获取 OpenSSL 私钥句柄
     *
     *
     * @throws RuntimeException 未设置私钥或加载失败
     *
     * @return OpenSSLAsymmetricKey 私钥句柄
     */
    private function getOpenSslPrivateKey(): OpenSSLAsymmetricKey
    {
        if (!$this->privateKeyPem) {
            throw new RuntimeException('未设置私钥');
        }
        $key = openssl_pkey_get_private($this->privateKeyPem, $this->passphrase ?? '');
        if ($key === false) {
            throw new RuntimeException('加载私钥失败');
        }

        return $key;
    }
}
