# Laravel 项目结构规范

## 1. 目录组织原则

### 1.1 按业务模块组织 vs 按技术类型组织

**推荐：按业务模块组织**（适用于中大型项目）

```
app/
├── Console/Commands/
├── Events/
│   └── Finance/           # 财务模块
│       └── InvoiceSubmitted.php
├── Http/
│   ├── Controllers/
│   │   └── User/          # 用户模块
│   └── Requests/
├── Listeners/
├── Models/
│   ├── Traits/
│   └── Observers/
├── Notifications/
│   └── Finance/           # 财务模块
├── Policies/
├── Services/
│   └── Finance/           # 财务模块
└── Providers/
```

**按技术类型组织**（适用于小型项目）

```
app/
├── Http/Controllers/      # 所有控制器
├── Models/                # 所有模型
├── Services/              # 所有服务类
└── Notifications/         # 所有通知
```

## 2. 推荐的项目结构

```
app/
├── Console/              # Artisan 命令
│   └── Commands/         # 自定义命令
├── Contracts/            # 接口定义
│   ├── Notification/     # 通知接口
│   └── Auth/             # 认证接口
├── Events/               # 事件类
│   └── Finance/          # 按业务模块组织
├── Exceptions/           # 异常处理
├── Http/
│   ├── Controllers/
│   │   └── User/         # 按功能模块组织
│   ├── Middleware/
│   └── Requests/         # 表单请求验证
├── Jobs/                 # 队列任务
├── Listeners/            # 事件监听器
├── Models/               # 数据模型
│   ├── Traits/           # 模型 trait
│   └── Observers/        # 模型观察器
├── Notifications/        # 通知类
│   └── Finance/          # 按业务模块组织
├── Policies/             # 授权策略
├── Providers/            # 服务提供者
└── Services/             # 业务逻辑服务
    └── Finance/          # 按业务模块组织
```

## 3. 各目录职责

### 3.1 Contracts/ - 接口定义

定义服务类的接口，便于替换实现和单元测试：

```php
// Contracts/Repository/UserRepositoryInterface.php
interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function create(array $data): User;
}
```

### 3.2 Services/ - 业务逻辑

服务类处理核心业务逻辑：

```php
// Services/Finance/InvoiceService.php
class InvoiceService
{
    public function __construct(
        protected InvoiceRepository $repository,
        protected InvoiceNotifier $notifier
    ) {}

    public function createForUser(User $user, array $data): Invoice
    {
        $invoice = $this->repository->create([
            'user_id' => $user->id,
            ...$data
        ]);

        $this->notifier->notifyAdmins($invoice);

        return $invoice;
    }
}
```

### 3.3 Events/ - 领域事件

事件类用于解耦业务逻辑：

```php
// Events/Finance/InvoiceSubmitted.php
class InvoiceSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public User $submitter
    ) {}
}
```

### 3.4 Listeners/ - 事件监听器

监听器处理事件的副作用：

```php
// Listeners/Finance/SendInvoiceNotification.php
class SendInvoiceNotification
{
    public function handle(InvoiceSubmitted $event): void
    {
        Notification::send(
            $event->invoice->user,
            new InvoiceSubmittedNotification($event->invoice)
        );
    }
}
```

### 3.5 Notifications/ - 通知类

通知类封装各种通知渠道：

```php
// Notifications/Finance/InvoiceApprovedNotification.php
class InvoiceApprovedNotification extends BaseNotification
{
    public function via(object $notifiable): array
    {
        return [WechatMiniChannel::class, 'mail'];
    }

    public function toWechatMini(object $notifiable): array
    {
        return [
            'data' => [
                'keyword1' => $this->invoice->title,
                'keyword2' => $this->invoice->amount,
            ],
        ];
    }
}
```

### 3.6 Models/Traits/ - 模型 Trait

Traits 用于提取和复用模型的公共行为。详细规范请参考 SKILL.md 中的 **3.3 Traits 规范**。

## 4. Models/Observers/ - 模型观察器

观察器用于监听模型事件：

```php
// Models/Observers/UserObserver.php
class UserObserver
{
    public function created(User $user): void
    {
        $user->profile()->create(['user_id' => $user->id]);
    }
}
```

## 5. 枚举的放置

枚举用于定义固定值集合。详细规范请参考 SKILL.md 中的 **3.4 枚举规范**。

## 6. 资源类文件的放置

### 6.1 API Resources

```
app/Http/Resources/
├── User/
│   └── UserResource.php
├── Invoice/
│   ├── InvoiceResource.php
│   └── InvoiceApplicationResource.php
└── Collections/
    └── UserCollection.php
```

### 6.2 Form Requests

```
app/Http/Requests/
├── Auth/
│   ├── LoginRequest.php
│   └── RegisterRequest.php
└── Finance/
    ├── CreateInvoiceApplicationRequest.php
    └── UpdateInvoiceRequest.php
```

### 6.3 Filament Resources

```
app/Filament/
├── Backend/
│   └── Clusters/
│       └── Finance/
│           └── Resources/
│               ├── InvoiceResource/
│               │   ├── InvoiceResource.php
│               │   ├── InvoiceResource/Pages/
│               │   └── InvoiceResource/RelationManagers/
│               └── InvoiceApplicationResource/
└── Tenant/
    └── Clusters/
        └── Finance/
            └── Resources/
                └── InvoiceApplicationResource/
```

## 7. 迁移文件组织

### 7.1 按功能合并迁移

将同一功能的所有表放在一个迁移文件中：

```
database/migrations/
├── 2026_04_03_100908_create_invoices_tables.php   # 发票相关所有表
├── 2026_04_10_000000_create_users_table.php
└── 2026_04_10_000001_create_tenants_tables.php
```

### 7.2 索引优化

```php
// 在创建表时直接定义索引
$table->index(['tenant_id', 'status']);        // 复合索引
$table->unique(['tenant_id', 'order_no']);   // 唯一索引
$table->index('deleted_at');                   // 软删除索引

// 条件索引
DB::statement('CREATE INDEX idx_active ON orders (tenant_id) WHERE deleted_at IS NULL');
```

---

**版本**：1.0.0
**最后更新**：2026-04-24
