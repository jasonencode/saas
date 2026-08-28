# 目录结构

## 整体结构

```
├── app/
│   ├── Channels/                # 通知通道（Sms、WechatMini、WechatOfficial、DingTalk、JPush、Tenant）
│   ├── Console/
│   │   └── Commands/            # Artisan 命令（按 Maintenance / Mall / Seeders / User 分组）
│   ├── Contracts/               # 契约接口（ServiceInterface、Policy、PolicyName、Orderable、ShouldPayment 等）
│   ├── Enums/                   # 枚举类（按业务模块分组）
│   ├── Events/                  # 事件（Mall / Finance / User 分组）
│   ├── Filament/
│   │   ├── Actions/             # 自定义 Actions（按业务模块分组）
│   │   ├── Backend/             # 平台面板
│   │   │   ├── Clusters/        # 集群（BlockChain、Campaign、Content、Finance、Foundation、Mall、Setting、User）
│   │   │   ├── Pages/           # 自定义页面
│   │   │   └── Resources/       # 集群外资源
│   │   ├── Tenant/              # 租户面板
│   │   ├── Exports/             # 导出任务
│   │   ├── Forms/               # 自定义表单组件
│   │   ├── Infolists/           # 自定义详情组件
│   │   └── Tables/              # 自定义表格组件
│   ├── Http/
│   │   ├── Controllers/         # 控制器（按业务模块分组）
│   │   ├── Handlers/            # API 异常处理器
│   │   ├── Middleware/          # 中间件
│   │   ├── Requests/            # FormRequest
│   │   ├── Resources/           # API Resource
│   │   └── Responses/           # ApiResponse 统一响应
│   ├── Jobs/                    # 队列任务（BaseJob 基类 + 业务分组）
│   ├── Listeners/               # 事件监听器
│   ├── Livewire/                # Livewire 组件
│   ├── Models/                  # 数据模型（按业务模块分组 + Traits + 基础 Model）
│   ├── Notifications/           # 通知类（Mall / Finance 分组）
│   ├── Policies/                # 权限策略（按业务模块分组）
│   ├── Providers/               # 服务提供者
│   ├── Rules/                   # 验证规则
│   ├── Services/                # 业务服务（按业务模块分组，实现 ServiceInterface）
│   └── Support/                 # helpers.php、TenantResolver、Filesystem、Tasks 等
├── bootstrap/                   # 引导文件
├── config/                      # 配置文件
├── database/
│   ├── factories/               # 模型工厂
│   ├── migrations/              # 数据迁移
│   └── seeders/                 # 数据填充
├── docs/                        # 开发文档（apis/ API 文档、development/ 方案、scripts/ 脚本）
├── public/
│   └── docs/                    # 本套文档（Docsify）
├── resources/
│   ├── css/                     # Filament 主题样式
│   └── views/                   # 视图文件
├── routes/
│   ├── api.php                  # API 主域名路由（健康检查、版本）
│   ├── apis/                    # 各模块 API（auth、chain、content、mall、campaign、user、finance）
│   ├── console.php              # 命令行路由（定时任务）
│   └── web.php                  # Web 路由
├── storage/                     # 存储文件
└── tests/                       # 测试文件
```

## 核心目录说明

### 分层架构

| 层 | 目录 | 职责 |
|----|------|------|
| HTTP 层 | `Http/Controllers` | 参数校验、调用服务、返回 API Resource |
| 业务层 | `Services` | 业务逻辑，实现 `ServiceInterface`，通过 `service()` 助手获取 |
| 数据层 | `Models` | Eloquent 模型，按业务模块分组 |
| 异步层 | `Jobs` | 队列任务，继承 `BaseJob` |

### Filament 集群

`app/Filament/Backend/Clusters/` 下每个集群独立维护 `Pages/`、`Resources/`、`Widgets/`：

- `Resources/{资源名}/` 内统一包含 `Pages/`、`Tables/`、`Schemas/`、`RelationManagers/`
- 资源入口文件为 `{资源名}Resource.php`

### 策略类

`app/Policies/` 下按业务模块分组，策略继承 `App\Contracts\Policy`：

- 用 `#[PolicyName('名称', type: PolicyType::Button)]` 注解定义权限名称
- 模型通过 `#[UsePolicy]` 注解绑定策略

### 辅助函数

`app/Support/helpers.php`（composer autoload files 自动加载）：

| 函数 | 说明 |
|------|------|
| `service()` | 获取实现 `ServiceInterface` 的服务实例 |
| `userCan()` | 当前用户权限判定 |
| `isBackend()` | 判断当前面板是否为 backend 面板 |
| `hideMobilePhoneNo()` | 隐藏手机号中间位 |
| `array2tree()` / `list2tree()` | 数组转树形结构 |
| `calculateDistance()` | 计算两点距离 |

### Blueprint 宏

`AppServiceProvider::bootBluePrint()` 注册的迁移快捷方法：`tenant()`、`user()`、`no()`、`cover()`、`pictures()`、`easyStatus()`、`sort()`、`regionAddress()` 等。
