# Export

基于 Maatwebsite/Excel 的导出封装，提供统一的基类处理文件名规范化、格式校验和查询构建器注入。

## 架构

```
BaseExport (抽象基类)
    ├── FromQuery        # 查询驱动导出
    ├── WithHeadings     # 表头定义
    ├── WithMapping      # 数据行映射
    └── Responsable      # HTTP 响应

UserExport (示例)
    └── extends BaseExport
```

## 目录结构

```
Export/
├── BaseExport.php   # 抽象基类，处理文件名/格式/查询
└── UserExport.php   # 用户导出示例
```

## 核心类

| 类 | 职责 |
|----|------|
| `BaseExport` | 抽象基类：注入 Builder、规范化文件名校验扩展名、定义格式白名单 |
| `UserExport` | 用户导出示例：CSV 格式、ID + 用户名 |

## 创建新 Export

1. 继承 `BaseExport`
2. 实现 `getFileName()`、`headings()`、`map()`

```php
<?php

namespace App\Support\Export;

use Filament\Actions\Exports\Enums\ExportFormat;

class OrderExport extends BaseExport
{
    protected ExportFormat $defaultFormat = ExportFormat::Xlsx;

    public function getFileName(): string
    {
        return '订单导出'.date('YmdHis');
    }

    public function headings(): array
    {
        return ['订单号', '金额', '状态'];
    }

    public function map(mixed $row): array
    {
        return [
            $row->no,
            $row->amount,
            $row->status->getLabel(),
        ];
    }
}
```

## 使用方式

通过 `CustomExportAction` 在 Filament 表格中使用：

```php
use App\Support\Export\OrderExport;

CustomExportAction::make()
    ->exporter(OrderExport::class)
```

## 配置

默认导出格式为 Xlsx，可在子类中覆盖 `$defaultFormat`。允许的格式：`Xlsx`、`Csv`。

## 依赖

- `maatwebsite/excel` ^3.1
