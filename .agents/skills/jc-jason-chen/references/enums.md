# Laravel 枚举规范

## 1. 概述

枚举（Enum）用于定义一组具名的固定值。在 Laravel 中，推荐使用 PHP 8.1+ 的原生枚举（Backed Enum），配合 Trait 实现标签、颜色等辅助功能。

## 2. 枚举放置

### 2.1 内联枚举（简单场景）

对于只在一个模型中使用的状态枚举，可以直接放在模型文件中：

```php
// app/Models/Order.php
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Completed = 'completed';

    public static function getLabel(?self $status): string
    {
        return match ($status) {
            self::Pending => '待支付',
            self::Paid => '已支付',
            self::Shipped => '已发货',
            self::Completed => '已完成',
        };
    }
}
```

### 2.2 独立枚举（复杂场景）

对于需要重用的枚举，放在 `app/Enums/` 目录：

```
app/
└── Enums/
    ├── InvoiceType.php
    ├── InvoiceStatus.php
    ├── InvoiceApplicationStatus.php
    └── OrderStatus.php
```

## 3. 枚举 Trait

### 3.1 HasLabel Trait

用于获取枚举的中文标签：

```php
// app/Enums/Traits/HasLabel.php
trait HasLabel
{
    public static function getLabel(?self $status): string
    {
        return match ($status) {
            default => $status?->value ?? '未知',
        };
    }

    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'name')
        );
    }
}
```

### 3.2 HasColor Trait

用于获取枚举对应的颜色值（适用于 Filament 等 UI 框架）：

```php
// app/Enums/Traits/HasColor.php
trait HasColor
{
    public static function getColor(?self $status): ?string
    {
        return match ($status) {
            default => null,
        };
    }

    public static function colors(): array
    {
        return [
            // value => color
        ];
    }
}
```

## 4. 完整枚举示例

```php
// app/Enums/InvoiceStatus.php
enum InvoiceStatus: string
{
    use HasLabel, HasColor;

    case Pending = 'pending';
    case Issued = 'issued';
    case Void = 'void';

    public static function getLabel(?self $status): string
    {
        return match ($status) {
            self::Pending => '待开票',
            self::Issued => '已开票',
            self::Void => '已作废',
        };
    }

    public static function getColor(?self $status): ?string
    {
        return match ($status) {
            self::Pending => 'warning',
            self::Issued => 'success',
            self::Void => 'danger',
        };
    }
}
```

## 5. 枚举使用

### 5.1 在模型中使用

```php
// app/Models/Invoice.php
class Invoice extends Model
{
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'type' => InvoiceType::class,
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return InvoiceStatus::getLabel($this->status);
    }
}
```

### 5.2 在 Filament 中使用

```php
// 在 Filament 表单或表格中
use Filament\Forms\Components\Select;

Select::make('status')
    ->options(InvoiceStatus::class)
    ->getOptionLabelFromStateUsing(fn (InvoiceStatus $status): string => $status->getLabel())
    ->colors([
        InvoiceStatus::Pending => 'warning',
        InvoiceStatus::Issued => 'success',
        InvoiceStatus::Void => 'danger',
    ])
```

### 5.3 在 Blade 中使用

```blade
<span class="text-{{ $invoice->status->getColor() }}">
    {{ $invoice->status->getLabel() }}
</span>
```

## 6. 命名规范

| 类型 | 命名方式 | 示例 |
|------|----------|------|
| 枚举类名 | PascalCase | `InvoiceStatus`, `OrderType` |
| 枚举 case | PascalCase | `case Pending = 'pending'` |
| 枚举值 | snake_case | `'pending'`, `'paid'` |
| Trait 名 | Has{Feature} | `HasLabel`, `HasColor` |

## 7. 判定标准

| 场景 | 内联枚举 | 独立枚举 |
|------|----------|----------|
| 只在一个模型中使用 | ✅ | ❌ |
| 需要在多个模型/服务中复用 | ❌ | ✅ |
| 状态值需要国际化 | ❌ | ✅ |
| 需要配合 Filament 等框架使用 | ❌ | ✅ |

---

**版本**：1.0.0
**最后更新**：2026-04-24