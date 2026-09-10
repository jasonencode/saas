<?php

namespace App\Support\AES;

use Random\RandomException;
use RuntimeException;

class AES
{
    /**
     * 加密算法
     */
    private const string CIPHER = 'AES-256-CBC';

    /**
     * 分隔符：分隔 base64(iv) 与 base64(ciphertext)
     */
    private const string SEPARATOR = '|';

    /**
     * 加密字符串
     *
     * @param  string  $plaintext  明文字符串
     *
     * @throws RandomException 随机数生成失败
     * @throws RuntimeException 密钥未配置或加密失败
     *
     * @return string 返回格式: base64(iv)|base64(ciphertext)
     */
    public static function encrypt(string $plaintext): string
    {
        $iv = random_bytes(self::ivLength());

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($ciphertext === false) {
            throw new RuntimeException('加密失败: '.openssl_error_string());
        }

        return base64_encode($iv).self::SEPARATOR.base64_encode($ciphertext);
    }

    /**
     * 解密字符串
     *
     * @param  string  $payload  密文字符串，格式: base64(iv)|base64(ciphertext)
     *
     * @throws RuntimeException 密文格式错误、密钥未配置或解密失败
     *
     * @return string 明文字符串
     */
    public static function decrypt(string $payload): string
    {
        $parts = explode(self::SEPARATOR, $payload);

        if (count($parts) !== 2) {
            throw new RuntimeException('密文格式错误');
        }

        [$encodedIv, $encodedCiphertext] = $parts;
        $iv = base64_decode($encodedIv, true);
        $ciphertext = base64_decode($encodedCiphertext, true);

        if ($iv === false || $ciphertext === false) {
            throw new RuntimeException('密文不是有效的Base64字符串');
        }

        if (strlen($iv) !== self::ivLength()) {
            throw new RuntimeException('密文IV长度错误');
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($plaintext === false) {
            throw new RuntimeException('解密失败: '.openssl_error_string());
        }

        return $plaintext;
    }

    /**
     * 获取派生后的二进制密钥
     *
     * @throws RuntimeException 未配置 custom.aes_key
     *
     * @return string 32 字节二进制密钥
     */
    private static function key(): string
    {
        $key = config('custom.aes_key');

        if (!is_string($key) || $key === '') {
            throw new RuntimeException('未配置AES密钥');
        }

        return hash('sha256', $key, true);
    }

    /**
     * 获取当前算法的 IV 长度（字节）
     *
     * @return int IV 长度
     */
    private static function ivLength(): int
    {
        return (int) openssl_cipher_iv_length(self::CIPHER);
    }
}
