<?php

namespace App\Support\Sigma;

/**
 * Sigma 校验码工具
 *
 * 为订单号生成并校验末位加权校验码。
 */
class Sigma
{
    protected static array $factor = [10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9];

    protected static array $verifyList = ['1', '0', '9', '8', '7', '6', '5', '4', '3', '2'];

    protected static int $modNumber = 10;

    /**
     * 生成带校验码的订单号
     *
     * @param  string  $str  订单号（仅支持数字）
     *
     * @return string 加权后的订单号（末位为校验码）
     */
    public static function orderNo(string $str): string
    {
        $sign = 0;
        for ($i = 0, $iMax = strlen($str); $i < $iMax; $i++) {
            $sign += (int) $str[$i] * self::$factor[$i];
        }
        $mod = $sign % self::$modNumber;

        return $str.self::$verifyList[$mod];
    }

    /**
     * 验证订单号校验码
     *
     * @param  string  $str  带校验码的订单号
     * @param  int  $prefixLen  前缀长度（跳过前 N 位不参与校验）
     *
     * @return bool 校验是否通过
     */
    public static function verify(string $str, int $prefixLen = 0): bool
    {
        $str = substr($str, $prefixLen);

        $sign = 0;
        for ($i = 0; $i < strlen($str) - 1; $i++) {
            $sign += (int) $str[$i] * self::$factor[$i];
        }
        $mod = $sign % self::$modNumber;

        return self::$verifyList[$mod] === substr($str, -1);
    }
}
