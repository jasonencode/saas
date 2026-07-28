---
name: filament-action-style
description: Filament Action代码风格规范，包括方法排序、getDefaultName命名、icon使用枚举、通知方式等。
origin: USER
---

# Filament Action 代码风格规范

适用于 Filament Action（包括 Action 和 BulkAction）的代码风格和结构规范。

## 适用场景

* 创建或修改 Filament Action 类
* 统一 Action 代码风格
* 规范方法调用顺序

## 规则

### 1. 文件结构

```php
<?php

namespace App\Filament\Actions\{Module};

use Filament\Actions\Action;
// 其他 use 语句...

class {Name}Action extends Action
{
    public static function getDefaultName(): ?string
    {
        return '{name}'; // 驼峰命名，去掉 Action 后缀
    }

    protected function setUp(): void
    {
        parent::setUp();

        // ... 方法调用（按顺序）
    }
}
```

### 2. getDefaultName 命名规范

返回值去掉 `Action` 后缀，使用小驼峰命名（camelCase）。

| 类名 | 返回值 |
|------|--------|
| `AccountAdjustmentAction` | `accountAdjustment` |
| `PaymentQueryAction` | `paymentQuery` |
| `SyncErpBookStoresAction` | `syncErpBookStores` |
| `RetryFailedJobByQueueAction` | `retryQueue` |

### 3. 方法排序规范

在 `setUp()` 方法中，按以下顺序调用方法：

```php
protected function setUp(): void
{
    parent::setUp();

    $this->label('标签文本');
    $this->icon(Heroicon::OutlinedXxx);
    $this->color('primary');

    $this->visible(fn (): bool => userCan(self::getDefaultName(), $record));
    $this->hidden(fn (): bool => $record->is_finished);

    $this->modalWidth(Width::Medium);
    $this->requiresConfirmation();
    $this->modalHeading('弹窗标题');
    $this->modalDescription('弹窗描述');
    $this->modalSubmitActionLabel('确认按钮文本');
    $this->modalSubmitAction(false);
    $this->modalCancelActionLabel('取消按钮文本');

    $this->fillForm(fn () => [...]);
    $this->schema([...]);

    $this->action(function (Model $record, array $data): void {
        $this->successNotificationTitle('操作成功');
        $this->success();
    });

    $this->url(fn () => route('...'), true);
    $this->deselectRecordsAfterCompletion();
}
```

### 4. icon 必须使用枚举（强制）

**禁止使用字符串形式，必须使用 `Heroicon` 枚举。**

```php
use Filament\Support\Icons\Heroicon;

// ✅ 正确
$this->icon(Heroicon::OutlinedReceiptRefund);
$this->icon(Heroicon::ArrowDownTray);
$this->icon(Heroicon::OutlinedTrash);

// ❌ 错误 - 禁止使用
$this->icon('heroicon-o-receipt-refund');
$this->icon('heroicon-o-arrow-down-tray');
```

### 5. 通知方式（强制）

**在 Action 中禁止使用 `Notification::make()`，必须使用 Action 自带的通知方法。**

```php
use Filament\Notifications\Notification;

// ❌ 错误 - 禁止使用
Notification::make()->success()->title('操作成功')->send();
Notification::make()->danger()->title('操作失败')->send();

// ✅ 正确 - 使用 Action 方法
$this->successNotificationTitle('操作成功');
$this->success();

$this->failureNotificationTitle('操作失败');
$this->failureNotificationBody('失败原因');
$this->failure();
```

### 6. 带条件的成功/失败通知

```php
$this->action(function (Model $record, array $data): void {
    $result = $record->exec($data);

    if ($result) {
        $this->successNotificationTitle('操作成功');
        $this->success();
    } else {
        $this->failureNotificationTitle('操作失败');
        $this->failure();
    }
});
```

## 完整示例

### 标准 Action

```php
<?php

namespace App\Filament\Actions\Mall;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;
use Modules\Mall\Models\OrderInvoice;

class InvoicePassAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'invoicePass';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('开票完成');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->color('success');

        $this->visible(fn (OrderInvoice $record): bool => $record->status === OrderInvoice::STATUS_INIT);

        $this->requiresConfirmation();
        $this->modalHeading('开票完成');
        $this->modalSubmitActionLabel('确认');

        $this->schema([
            Forms\Components\TextInput::make('cover')
                ->label('发票图片')
                ->image(),
            Forms\Components\TextInput::make('link')
                ->label('发票下载链接')
                ->url(),
        ]);

        $this->action(function (OrderInvoice $record, array $data): void {
            $record->update([
                'cover' => $data['cover'] ?? null,
                'link' => $data['link'] ?? null,
                'status' => OrderInvoice::STATUS_SUCCESS,
                'opened_at' => now(),
            ]);

            $this->successNotificationTitle('操作完成');
            $this->success();
        });
    }
}
```

### 带权限的 Action

```php
<?php

namespace App\Filament\Actions\Setting;

use App\Models\System\FailedJob;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class RetryFailedJobAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'retryFailedJob';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('重试任务');
        $this->icon(Heroicon::OutlinedReceiptRefund);

        $this->visible(fn (): bool => userCan(self::getDefaultName(), FailedJob::class));

        $this->requiresConfirmation();

        $this->action(function (): void {
            Artisan::call('queue:retry all');
            $this->successNotificationTitle('重试任务提交成功');
            $this->success();
        });
    }
}
```

### BulkAction 示例

```php
<?php

namespace App\Filament\Actions\Common;

use App\Models\Model;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Collection;

class DisableBulkAction extends BulkAction
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'disableBulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量禁用');
        $this->icon(Heroicon::OutlinedMoon);

        $this->requiresConfirmation();
        $this->successNotificationTitle('已禁用选中项目');

        $this->deselectRecordsAfterCompletion();

        $this->visible(fn (HasTable $livewire): bool => userCan(self::getDefaultName(), $livewire->getTable()->getModel()));

        $this->hidden(function (HasTable $livewire): bool {
            $trashedFilterState = $livewire->getTableFilterState(Tables\Filters\TrashedFilter::class) ?? [];
            if (!array_key_exists('value', $trashedFilterState)) {
                return false;
            }
            if ($trashedFilterState['value']) {
                return false;
            }

            return filled($trashedFilterState['value']);
        });

        $this->action(function (): void {
            $this->process(static fn (Collection $records) => $records->each(fn (Model $record) => $record->disable()));

            $this->success();
        });
    }
}
```

### 只读 Modal（无提交按钮）

```php
<?php

namespace App\Filament\Actions\Finance;

use App\Filament\Infolists\Components\TextareaEntry;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Modules\Payment\Models\Payment;

class PaymentQueryAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'paymentQuery';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('支付查询');
        $this->icon(Heroicon::OutlinedMagnifyingGlass);
        $this->color('info');

        $this->modalHeading('支付查询结果');
        $this->modalSubmitAction(false);
        $this->modalCancelActionLabel('关闭');

        $this->action(fn () => null);

        $this->schema(function (Payment $record): array {
            $result = $record->getAdapter()->query();

            return [
                TextareaEntry::make('result')
                    ->label('API结果')
                    ->state($result)
                    ->rows(15),
            ];
        });
    }
}
```

## 常用配置参考

### 颜色值

| 值 | 说明 |
|------|------|
| `primary` | 主色（默认） |
| `success` | 成功/绿色 |
| `warning` | 警告/黄色 |
| `danger` | 危险/红色 |
| `info` | 信息/蓝色 |
| `gray` | 灰色 |

### Heroicon 枚举

| 图标 | 用途 |
|------|------|
| `Heroicon::ArrowDownTray` | 下载 |
| `Heroicon::ArrowLeft` | 返回 |
| `Heroicon::OutlinedTrash` | 删除 |
| `Heroicon::OutlinedReceiptRefund` | 重试 |
| `Heroicon::OutlinedPrinter` | 打印 |
| `Heroicon::OutlinedTruck` | 物流 |
| `Heroicon::OutlinedArrowsUpDown` | 排序 |
| `Heroicon::OutlinedMoon` | 禁用 |
| `Heroicon::OutlinedSun` | 启用 |
| `Heroicon::OutlinedArrowPath` | 刷新 |
| `Heroicon::OutlinedWrenchScrewdriver` | 工具/调账 |
| `Heroicon::OutlinedMagnifyingGlass` | 查询/搜索 |
| `Heroicon::OutlinedArrowUturnLeft` | 退款/返回 |
| `Heroicon::OutlinedKey` | 密码/密钥 |
| `Heroicon::OutlinedCheckCircle` | 完成/通过 |
| `Heroicon::OutlinedXCircle` | 拒绝/失败 |
