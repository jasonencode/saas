<?php

namespace App\Support\Tenant;

use Random\RandomException;

/**
 * 租户凭据生成器
 *
 * App Key 使用 Bitcoin Base58 字母表（去除 0/O/I/l 等易混淆字符），
 * 固定 16 位并以 1 开头；App Secret 为 32 位十六进制字符串。
 */
final class AppCredentialGenerator
{
    /**
     * Base58 字母表（Bitcoin 变体）
     */
    private const string ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    /**
     * App Key 长度
     */
    private const int KEY_LENGTH = 16;

    /**
     * 生成租户 App Key
     *
     * @throws RandomException
     *
     * @return string 16 位 Base58 App Key（1 开头）
     */
    public static function appKey(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $key = '1';

        for ($i = 1; $i < self::KEY_LENGTH; $i++) {
            $key .= self::ALPHABET[random_int(0, $max)];
        }

        return $key;
    }

    /**
     * 生成租户 App Secret
     *
     * @throws RandomException
     *
     * @return string 32 位十六进制 App Secret
     */
    public static function appSecret(): string
    {
        return md5(uniqid(random_bytes(16), true));
    }
}
