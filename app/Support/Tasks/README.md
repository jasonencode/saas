# Settlement Tasks

结算任务（Settlement Tasks）是结算流程中的独立步骤，通过 Laravel Pipeline 按顺序编排执行。

## 架构

```
SettlementService::execute($voucher)
    └── Pipeline::send($data)
        ├── DirectReward::handle()    # 步骤 1
        ├── SecondReward::handle()    # 步骤 2
        └── then()                    # 标记完成
```

每个 Task 实现 `SettlementTask` 接口，通过 `$next($data)` 将控制权传递给下一个步骤。

## 数据库配置

任务的执行参数存储在 `tasks` 表的 `options` JSON 字段中，通过 `WithDefaultSetting` trait 自动合并到任务实例：

```json
{
    "amount": 10,
    "asset": "points"
}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| `amount` | float | 奖励数量 |
| `asset` | string | 资产类型：`points`（积分）或 `balance`（余额） |

## 注册任务

在 `AppServiceProvider::bootSettlementTasks()` 中批量注册：

```php
TaskService::registerMany([
    DirectReward::class,
    SecondReward::class,
]);
```

新增 Task 后只需追加到此数组。

## 创建新 Task

1. 在 `app/Support/Tasks/` 下创建类，实现 `SettlementTask` 接口
2. 使用 `WithDefaultSetting` trait 自动合并数据库配置
3. 在 `AppServiceProvider` 中注册

```php
<?php

namespace App\Support\Tasks;

use App\Contracts\SettlementTask;
use App\Contracts\SettleTaskData;
use App\Support\Tasks\Traits\WithDefaultSetting;
use Closure;

class MyTask implements SettlementTask
{
    use WithDefaultSetting;

    protected array $options = [
        'amount' => 0,
    ];

    public function getTitle(): string
    {
        return '任务名称';
    }

    public function getDescription(): string
    {
        return '任务描述';
    }

    public function handle(SettleTaskData $data, Closure $next): mixed
    {
        // 业务逻辑...

        return $next($data);
    }
}
```

## 可用 Task

| Task | 说明 |
|------|------|
| `DirectReward` | 直推奖励 - 推荐人直接获得奖励 |
| `SecondReward` | 二级推荐奖励 - 推荐人的上级获得奖励 |

## 关键类

| 类 | 路径 | 职责 |
|----|------|------|
| `SettlementTask` | `App\Contracts` | Task 接口定义 |
| `SettleTaskData` | `App\Contracts` | Pipeline 传递的 DTO，包含 Voucher 和可变参数 |
| `TaskService` | `App\Services\Finance` | Task 注册、解析、列表 |
| `SettlementService` | `App\Services\Finance` | 结算编排器，驱动 Pipeline 执行 |
