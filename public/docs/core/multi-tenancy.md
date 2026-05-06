# 多租户架构

## 概述

JasonSaaS 内置多租户支持，通过 `IsTenant` trait 实现租户隔离。

## 使用方式

### 模型 Trait

```php
<?php

namespace App\Models;

use App\Models\Traits\IsTenant;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use IsTenant;
}
```

### 租户模型

```php
use Stancl\Tenancy\Database\Models\Tenant;

class Team extends Tenant
{
    protected $fillable = [
        'name',
        'domain',
    ];
}
```

## 租户识别

根据请求自动识别当前租户：

1. **域名识别** - 通过子域名识别
2. **路径识别** - 通过 URL 路径识别
3. **请求头识别** - 通过请求头识别

## 数据隔离

所有使用 `IsTenant` trait 的模型会自动实现：
- 自动添加 `tenant_id` 字段
- 全局查询作用域自动过滤
- 创建时自动注入当前租户 ID