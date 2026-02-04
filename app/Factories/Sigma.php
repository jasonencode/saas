<?php

namespace App\Factories;

class Sigma
{
    protected static array $factor = [10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9];

    protected static array $verifyList = ['1', '0', '9', '8', '7', '6', '5', '4', '3', '2'];

    protected static int $modNumber = 10;

    /**
     * Notes   : 加权求和的方式给订单号增加了一位数字，参考身份证的验签方式
     *
     * @Date   : 2023/3/28 17:00
     * @Author : <Jason.C>
     * @param  string  $str  订单号，仅支持数字
     * @return string 加权后的订单号，末位为校验码
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
