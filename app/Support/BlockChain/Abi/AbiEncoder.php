<?php

namespace App\Support\BlockChain\Abi;

use InvalidArgumentException;
use JsonException;

/**
 * Solidity ABI 编码器
 *
 * 支持构造函数参数编码、类型化参数列表编码，
 * 覆盖 uint/int/address/bool/bytes/string/数组/tuple 等类型。
 */
class AbiEncoder
{
    /**
     * 根据 ABI 规范编码构造函数参数
     *
     * @param  string  $abiJson  完整的 ABI JSON 字符串
     * @param  array  $args  构造函数参数值
     *
     * @throws JsonException
     *
     * @return string 十六进制编码的参数数据（不含 0x 前缀）
     */
    public static function encodeConstructor(string $abiJson, array $args): string
    {
        if (empty($args)) {
            return '';
        }

        $abi = json_decode($abiJson, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($abi)) {
            return '';
        }

        // 查找构造函数定义
        $constructor = null;
        foreach ($abi as $entry) {
            if (($entry['type'] ?? '') === 'constructor') {
                $constructor = $entry;
                break;
            }
        }

        if ($constructor === null || empty($constructor['inputs'])) {
            return '';
        }

        $types = [];
        foreach ($constructor['inputs'] as $input) {
            $types[] = $input['type'];
        }

        return self::encodeParameters($types, $args);
    }

    /**
     * 编码类型化参数列表
     *
     * @param  array  $types  Solidity 类型字符串（如 ['uint256', 'address', 'string']）
     * @param  array  $args  对应的参数值
     *
     * @return string 十六进制编码数据
     */
    public static function encodeParameters(array $types, array $args): string
    {
        $headSize = count($types) * 32;

        // 第一遍：收集动态类型数据
        $tailData = [];
        foreach ($types as $i => $type) {
            $value = $args[$i] ?? null;
            $type = trim($type);

            if (self::isDynamicType($type)) {
                $tailData[$i] = self::encodeValue($type, $value);
            }
        }

        // 第二遍：构建头部（静态值 + 动态偏移指针）
        $head = '';
        $tailOffset = 0;

        foreach ($types as $i => $type) {
            $value = $args[$i] ?? null;
            $type = trim($type);

            if (self::isDynamicType($type)) {
                // 偏移量 = 头部大小 + 之前所有尾部数据大小之和
                $offset = $headSize + $tailOffset;
                $head .= self::encodeUint($offset);

                // 累加尾部偏移量供下一个动态类型使用
                $tailOffset += strlen($tailData[$i]) / 2;
            } else {
                $head .= self::encodeValue($type, $value);
            }
        }

        return $head.implode('', $tailData);
    }

    /**
     * 判断类型是否为动态大小（bytes、string、数组、元组）
     *
     * @param  string  $type  Solidity 类型
     *
     * @return bool 是否为动态类型
     */
    private static function isDynamicType(string $type): bool
    {
        if ($type === 'bytes' || $type === 'string') {
            return true;
        }

        if (str_ends_with($type, '[]')) {
            return true;
        }

        if (str_starts_with($type, 'tuple')) {
            return true;
        }

        return false;
    }

    /**
     * 根据 Solidity 类型编码单个值
     *
     * @param  string  $type  Solidity 类型
     * @param  mixed  $value  要编码的值
     *
     * @return string 十六进制编码值
     */
    private static function encodeValue(string $type, mixed $value): string
    {
        // uint<M>
        if (preg_match('/^uint(\d+)?$/', $type)) {
            return self::encodeUint((int) $value);
        }

        // int<M>
        if (preg_match('/^int(\d+)?$/', $type, $m)) {
            $bits = isset($m[1]) ? (int) $m[1] : 256;

            return self::encodeInt((int) $value, $bits);
        }

        // address
        if ($type === 'address') {
            return self::encodeAddress($value);
        }

        // bool
        if ($type === 'bool') {
            return self::encodeBool((bool) $value);
        }

        // bytes<M>
        if (preg_match('/^bytes(\d+)$/', $type, $m)) {
            return self::encodeFixedBytes($value, (int) $m[1]);
        }

        // bytes (dynamic)
        if ($type === 'bytes') {
            return self::encodeDynamicBytes($value);
        }

        // string
        if ($type === 'string') {
            return self::encodeString($value);
        }

        // <type>[] (dynamic array)
        if (str_ends_with($type, '[]')) {
            return self::encodeArray($type, $value);
        }

        throw new InvalidArgumentException("Unsupported ABI type: $type");
    }

    /**
     * 编码无符号整数（大端序，32 字节，左侧补零）
     *
     * @param  int  $value  无符号整数值
     *
     * @return string 十六进制编码（64 字符）
     */
    private static function encodeUint(int $value): string
    {
        $hex = dechex($value);

        return str_pad($hex, 64, '0', STR_PAD_LEFT);
    }

    /**
     * 编码有符号整数（负数使用二进制补码）
     *
     * @param  int  $value  有符号整数值
     * @param  int  $bits  位宽（默认 256）
     *
     * @return string 十六进制编码（64 字符）
     */
    private static function encodeInt(int $value, int $bits = 256): string
    {
        if ($value >= 0) {
            return self::encodeUint($value);
        }

        // 二进制补码：2^bits + value（value 为负数）
        // 对于大位宽整数，使用手动计算
        if ($bits <= 62) {
            $max = 1 << $bits;
            $complement = $max + $value;

            return str_pad(dechex($complement), 64, '0', STR_PAD_LEFT);
        }

        // 大位宽手动计算
        $hex = dechex(abs($value));
        $hex = str_pad($hex, 64, '0', STR_PAD_LEFT);

        // 按位取反后加 1 得到补码
        $inverted = '';
        for ($i = 0; $i < 64; $i++) {
            $n = hexdec($hex[$i]);
            $inverted .= dechex(15 - $n);
        }

        // 加 1
        $carry = 1;
        $result = '';
        for ($i = 63; $i >= 0; $i--) {
            $n = hexdec($inverted[$i]) + $carry;
            $carry = $n >> 4;
            $result = dechex($n & 15).$result;
        }

        return $result;
    }

    /**
     * 编码以太坊地址（20 字节，左侧填充至 32 字节）
     *
     * @param  mixed  $value  地址（含或不含 0x 前缀）
     *
     * @return string 十六进制编码（64 字符）
     */
    private static function encodeAddress(mixed $value): string
    {
        $addr = str_replace('0x', '', (string) $value);

        return str_pad(strtolower($addr), 64, '0', STR_PAD_LEFT);
    }

    /**
     * 编码布尔值（0 或 1，左侧填充至 32 字节）
     *
     * @param  bool  $value  布尔值
     *
     * @return string 十六进制编码（64 字符）
     */
    private static function encodeBool(bool $value): string
    {
        return str_pad($value ? '1' : '0', 64, '0', STR_PAD_LEFT);
    }

    /**
     * 编码固定长度 bytes<M>（右侧填充至 32 字节）
     *
     * @param  mixed  $value  字节值（十六进制）
     * @param  int  $length  字节长度
     *
     * @return string 十六进制编码（64 字符）
     */
    private static function encodeFixedBytes(mixed $value, int $length): string
    {
        $hex = str_replace('0x', '', (string) $value);

        return str_pad(substr($hex, 0, $length * 2), 64, '0');
    }

    /**
     * 编码动态 bytes 值（长度前缀 + 数据，32 字节对齐）
     *
     * @param  mixed  $value  字节值（十六进制）
     *
     * @return string 十六进制编码
     */
    private static function encodeDynamicBytes(mixed $value): string
    {
        $hex = str_replace('0x', '', (string) $value);

        if (strlen($hex) % 2 !== 0) {
            $hex = '0'.$hex;
        }

        $length = strlen($hex) / 2;
        $lengthHex = self::encodeUint($length);
        $paddedHex = str_pad($hex, self::ceil32(strlen($hex)), '0');

        return $lengthHex.$paddedHex;
    }

    /**
     * 编码字符串值（UTF-8，与动态 bytes 相同）
     *
     * @param  mixed  $value  字符串值
     *
     * @return string 十六进制编码
     */
    private static function encodeString(mixed $value): string
    {
        $hex = bin2hex((string) $value);
        $length = strlen((string) $value);
        $lengthHex = self::encodeUint($length);
        $paddedHex = str_pad($hex, self::ceil32(strlen($hex)), '0');

        return $lengthHex.$paddedHex;
    }

    /**
     * 编码数组（长度前缀 + 元素）
     *
     * @param  string  $type  数组类型（如 uint256[]）
     * @param  mixed  $value  数组值
     *
     * @return string 十六进制编码
     */
    private static function encodeArray(string $type, mixed $value): string
    {
        $values = (array) $value;
        $elementType = substr($type, 0, -2);

        $lengthHex = self::encodeUint(count($values));

        if (self::isDynamicType($elementType)) {
            // 动态类型数组：每个元素的偏移量，然后是数据
            $head = '';
            $elementData = [];

            // First encode all elements
            foreach ($values as $item) {
                $elementData[] = self::encodeValue($elementType, $item);
            }

            // Calculate offsets
            $headSize = (count($values) + 1) * 32; // count + offsets
            $currentOffset = $headSize;

            foreach ($elementData as $data) {
                $head .= self::encodeUint($currentOffset);
                $currentOffset += strlen($data) / 2;
            }

            return $lengthHex.$head.implode('', $elementData);
        }

        // 静态类型数组：元素拼接
        $encoded = '';
        foreach ($values as $item) {
            $encoded .= self::encodeValue($elementType, $item);
        }

        return $lengthHex.$encoded;
    }

    /**
     * 向上取整到 32 字节的倍数
     *
     * @param  int  $bytes  字节数
     *
     * @return int 取整后的字节数
     */
    private static function ceil32(int $bytes): int
    {
        return (int) (ceil($bytes / 32) * 32);
    }
}
