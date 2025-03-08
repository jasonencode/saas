<?php

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Notes: 用户权限判定
 *
 * @param  string  $ability
 * @param  string|Model  $model
 * @return bool
 */
function userCan(string $ability, string|Model $model): bool
{
    $user = Auth::user();

    return $user instanceof Authorizable && $user->can($ability, $model);
}

/**
 * Notes   : 隐藏字符串中间的N位
 *
 * @Date   : 2023/7/19 15:12
 * @Author : <Jason.C>
 * @param  string  $mobile  手机号
 * @param  int  $len  隐藏位数
 * @param  string  $char  填充符号
 * @return string
 */
function hideMobilePhoneNo(string $mobile, int $len = 4, string $char = '*'): string
{
    // 验证输入
    if (empty($mobile)) {
        throw new InvalidArgumentException('字符串不能为空');
    }

    $strLen = mb_strlen($mobile);

    // 如果隐藏长度大于等于字符串长度，取字符串长度的一半
    if ($len >= $strLen) {
        $len = (int) ceil($strLen / 2);
    }

    // 计算前后显示的长度
    $leftLength = (int) floor(($strLen - $len) / 2);
    $rightLength = (int) ceil(($strLen - $len) / 2);

    // 使用 mb_substr 处理多字节字符
    return mb_substr($mobile, 0, $leftLength).
        str_repeat($char, $len).
        mb_substr($mobile, -$rightLength);
}

/**
 * 数组转换为树型结构，用于无限极分类
 *
 * @param  array  $list
 * @param  int  $parentId
 * @param  string  $parentNodeName
 * @param  string  $primaryKey
 * @param  string  $childNodeName
 * @return array
 */
function array2tree(
    array $list,
    int $parentId = 0,
    string $primaryKey = 'id',
    string $parentNodeName = 'parent_id',
    string $childNodeName = 'children'
): array {
    $resultArr = [];
    if (empty($list)) {
        return [];
    }
    foreach ($list as $key => $item) {
        if ($item[$parentNodeName] == $parentId) {
            unset($list[$key]);
            $item[$childNodeName] = array2tree(
                $list,
                $item[$primaryKey],
                $primaryKey,
                $parentNodeName,
                $childNodeName
            );
            $resultArr[$key] = $item;
        }
    }

    return $resultArr;
}

/**
 * 将扁平数组转换为树形结构
 *
 * @param  array  $list  原始数组
 * @param  string  $primaryKey  主键字段名
 * @param  string  $parentKey  父级字段名
 * @param  string  $childrenKey  子级字段名
 * @param  mixed  $parentValue  顶级父级值
 * @return array
 * @throws InvalidArgumentException
 */
function list2tree(
    array $list,
    string $primaryKey = 'id',
    string $parentKey = 'parent_id',
    string $childrenKey = 'children',
    mixed $parentValue = 0
): array {
    // 空数组快速返回
    if (empty($list)) {
        return [];
    }

    // 验证必要字段
    foreach ($list as $item) {
        if (!isset($item[$primaryKey], $item[$parentKey])) {
            throw new InvalidArgumentException(
                sprintf('数组项缺少必要字段：%s 或 %s', $primaryKey, $parentKey)
            );
        }
    }

    // 构建索引数组
    $refer = [];
    foreach ($list as $key => $item) {
        $refer[$item[$primaryKey]] = &$list[$key];
        // 初始化子节点数组
        $list[$key][$childrenKey] = [];
    }

    // 构建树形结构
    $tree = [];
    foreach ($list as $key => $item) {
        $parentId = $item[$parentKey];

        if ($parentId === $parentValue) {
            // 顶级节点
            $tree[] = &$list[$key];
        } elseif (isset($refer[$parentId])) {
            // 子级节点
            $refer[$parentId][$childrenKey][] = &$list[$key];
        }
    }

    // 移除空的子节点数组
    return array_map(function($item) use ($childrenKey) {
        if (empty($item[$childrenKey])) {
            unset($item[$childrenKey]);
        }

        return $item;
    }, $tree);
}

/**
 * Notes   : 格式化字节大小
 *
 * @Date   : 2023/8/1 17:37
 * @Author : <Jason.C>
 * @param  int  $size
 * @param  int  $decimals
 * @return string
 */
function formatBytes(int $size, int $decimals = 2): string
{
    $sign = $size < 0 ? '-' : '';
    $size = abs($size);
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
    if ($size === 0) {
        return "0 B";
    }
    $power = min(floor(log($size, 1024)), count($units) - 1);
    $size /= pow(1024, $power);

    return sprintf(
        '%s%s %s',
        $sign,
        number_format($size, $decimals, '.', ''),
        $units[$power]
    );
}

/**
 * 计算两个经纬度坐标之间的距离
 * 使用 Haversine 公式计算球面两点间的距离
 *
 * @param  float  $lat1  起点纬度
 * @param  float  $lng1  起点经度
 * @param  float  $lat2  终点纬度
 * @param  float  $lng2  终点经度
 * @param  float  $earthRadius  地球半径（千米）
 *                             6371.0    - 地球平均半径
 *                             6378.137  - WGS-84椭球体赤道半径
 *                             6356.752  - WGS-84椭球体极半径
 * @return float 距离（米）
 * @throws InvalidArgumentException
 */
function calculateDistance(
    float $lat1,
    float $lng1,
    float $lat2,
    float $lng2,
    float $earthRadius = 6371.0
): float {
    // 验证经纬度范围
    if ($lat1 < -90 || $lat1 > 90 || $lat2 < -90 || $lat2 > 90) {
        throw new InvalidArgumentException('纬度必须在-90到90度之间');
    }
    if ($lng1 < -180 || $lng1 > 180 || $lng2 < -180 || $lng2 > 180) {
        throw new InvalidArgumentException('经度必须在-180到180度之间');
    }

    // 如果坐标相同，直接返回0
    if ($lat1 === $lat2 && $lng1 === $lng2) {
        return 0.0;
    }

    // 转换为弧度
    $radLat1 = deg2rad($lat1);
    $radLat2 = deg2rad($lat2);
    $latDiff = $radLat1 - $radLat2;
    $lngDiff = deg2rad($lng1 - $lng2);

    // Haversine 公式
    $sinLat = sin($latDiff / 2);
    $sinLng = sin($lngDiff / 2);
    $a = $sinLat * $sinLat + cos($radLat1) * cos($radLat2) * $sinLng * $sinLng;

    $distance = 2 * asin(sqrt($a));

    // 转换为米并保留两位小数
    return round($distance * $earthRadius * 1000, 2);
}

/**
 * 对数组进行分组，按照前缀
 *
 * @param  array  $originalArray
 * @return array
 */
function groupArrayByPrefix(array $originalArray): array
{
    $groupedArray = [];

    foreach ($originalArray as $key => $value) {
        if (preg_match('/^(.*?)[-_](.*)$/', $key, $matches)) {
            $prefix = $matches[1];
            $suffix = $matches[2];

            if (!isset($groupedArray[$prefix])) {
                $groupedArray[$prefix] = [];
            }

            $groupedArray[$prefix][$suffix] = $value;
        }
    }

    return array_map(function($items) {
        return $items;
    }, $groupedArray);
}

/**
 * 对金额进行标准化格式转换 200000.00
 *
 * @param  string  $amount
 * @param  int  $decimals
 * @param  bool  $thousandsSeparator
 * @return string
 */
function amountFormat(string $amount, int $decimals = 2, bool $thousandsSeparator = false): string
{
    if ($thousandsSeparator) {
        return number_format($amount, $decimals);
    }

    return number_format($amount, $decimals, '.', '');
}