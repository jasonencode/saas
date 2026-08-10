# Sigma

订单号校验码生成器，使用加权校验和算法为订单号附加校验位，用于检测录入/传输错误。

## 核心类

| 类 | 职责 |
|----|------|
| `Sigma` | 静态工具：`orderNo()` 生成带校验位的订单号，`verify()` 验证校验位 |

## 算法

使用循环加权因子 `[10, 5, 8, 4, 2, 1, 6, 3, 7, 9, ...]`，对每位数字加权求和后模 10 取校验位。

## 使用方式

```php
use App\Support\Sigma\Sigma;

// 生成带校验位的订单号
$orderNo = Sigma::orderNo('250810120001'); // → '2508101200013'

// 验证校验位
$valid = Sigma::verify('2508101200013'); // → true

// 跳过前缀验证
$valid = Sigma::verify('ORD-2508101200013', prefixLen: 4); // → true
```

## 集成方式

通过 `AutoCreateOrderNo` trait 自动应用到 Eloquent 模型：

```php
use App\Models\Traits\AutoCreateOrderNo;

class Order extends Model
{
    use AutoCreateOrderNo;

    protected string $orderNoPrefix = 'ORD';
}
```

模型 `creating` 事件自动生成唯一订单号（含校验位 + 碰撞重试）。

## 依赖

无外部依赖。
