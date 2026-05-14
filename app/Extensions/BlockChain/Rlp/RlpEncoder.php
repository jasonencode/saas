<?php

namespace App\Extensions\BlockChain\Rlp;

class RlpEncoder
{
    /**
     * 递归 RLP 编码
     *
     * @param  string|array|int  $input  二进制字符串、项目数组或整数
     * @return string  RLP 编码后的二进制字符串
     */
    public static function encode(string|array|int $input): string
    {
        if (is_int($input)) {
            return self::encodeInteger($input);
        }

        if (is_array($input)) {
            return self::encodeList($input);
        }

        return self::encodeString($input);
    }

    /**
     * 编码单个二进制字符串
     */
    private static function encodeString(string $str): string
    {
        $len = strlen($str);

        if ($len === 0) {
            return "\x80";
        }

        if ($len === 1 && ord($str) < 0x80) {
            return $str;
        }

        if ($len <= 55) {
            return chr(0x80 + $len).$str;
        }

        $lenHex = self::encodeLength($len);

        return chr(0xb7 + strlen($lenHex)).$lenHex.$str;
    }

    /**
     * 编码列表（RLP 编码项目数组）
     */
    private static function encodeList(array $items): string
    {
        $payload = '';

        foreach ($items as $item) {
            $payload .= self::encode($item);
        }

        $len = strlen($payload);

        if ($len <= 55) {
            return chr(0xc0 + $len).$payload;
        }

        $lenHex = self::encodeLength($len);

        return chr(0xf7 + strlen($lenHex)).$lenHex.$payload;
    }

    /**
     * 编码大于 55 字节的值的长度
     */
    private static function encodeLength(int $length): string
    {
        $hex = dechex($length);

        return hex2bin($hex);
    }

    /**
     * 将整数编码为 RLP 字节串（大端序，无前导零）
     */
    private static function encodeInteger(int $value): string
    {
        if ($value === 0) {
            return "\x80";
        }

        $hex = dechex($value);

        // 确保十六进制长度为偶数
        if (strlen($hex) % 2 === 1) {
            $hex = '0'.$hex;
        }

        $bin = hex2bin($hex);

        return self::encodeString($bin);
    }
}
