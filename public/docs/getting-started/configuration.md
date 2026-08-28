# 配置说明

## 应用配置

### .env 文件

主要配置项：

```env
APP_NAME=Saas.Foundation
APP_ENV=local
APP_DEBUG=true
APP_URL=https://localhost
APP_TIMEZONE=Asia/Shanghai

DB_CONNECTION=pgsql
DB_HOST=postgresql
DB_PORT=5432
DB_DATABASE=saas
DB_USERNAME=postgres
DB_PASSWORD=

SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

### 域名与租户配置

`config/custom.php` 的 `domains` 配置，由以下环境变量控制：

| 环境变量 | 说明 |
|----------|------|
| `DEFAULT_DOMAIN` | 默认域名 |
| `API_DOMAIN` | API 域名，所有 `routes/apis/` 下的路由都绑定到该域名 |
| `BACKEND_DOMAIN` | 平台面板（`/backend`）域名 |
| `TENANT_DOMAIN` | 租户面板（`/tenant`）域名，Filament 多租户按该域名解析租户 |
| `FORCE_HTTPS` | 强制 HTTPS |
| `SERVER_ID` | 服务器标识，Horizon 主进程命名用 |

### 其他常用配置

| 环境变量 | 说明 |
|----------|------|
| `BCRYPT_ROUNDS` | 密码哈希成本因子，默认 12 |
| `MALL_ORDER_EXPIRED_MINUTES` | 订单支付过期时间（分钟），默认 30 |
| `HASHID_SALT` / `HASHID_LENGTH` / `HASHID_ALPHABET` | 对外 ID 的 Hashids 编码配置 |
| `HORIZON_DOMAIN` / `HORIZON_PATH` | Horizon 面板域名与路径（默认 `/backend/horizon`） |
| `WECHAT_OFFICIAL_ACCOUNT_*` | 微信公众号配置（EasyWeChat） |
| `OSS_*` / `AWS_*` | 对象存储（阿里云 OSS / S3 兼容） |

> 频率限制配置见 [频率限制](../guide/rate-limits)。

## 数据库配置

### PostgreSQL

项目以 PostgreSQL 为主要数据库，`Searchable` trait 的模糊/全文搜索按 pgsql 驱动自动适配 `ILIKE` / `to_tsvector`。

### Redis

Redis 用于：

- 缓存（`CACHE_STORE=redis`，`REDIS_CACHE_DB`）
- 队列驱动（`QUEUE_CONNECTION=redis`）
- Session 存储（`SESSION_DRIVER=redis`）
- Horizon 监控（`REDIS_DB`）
- 租户数据缓存（`tenant_data:v3:{tenantId}`，1 小时）

## 权限配置

权限定义在 `app/Policies` 目录下，策略类继承 `App\Contracts\Policy` 基类，通过 `#[UsePolicy]` 注解绑定到模型（自动注册，无需手动 `Gate::policy`）。

### 创建新权限

1. 在对应的 Policy 中添加方法
2. 使用 `#[PolicyName('权限名称', type: PolicyType::Button)]` 注解
3. 在 Action 中通过 `userCan()` 辅助函数检查权限

详见 [权限系统](../core/permissions) 与 [策略设计](../guide/policies)。

## Horizon 配置

Horizon 相关配置在 `config/horizon.php` 中：

- `HORIZON_DOMAIN` / `HORIZON_PATH`：面板访问路径
- Supervisor 进程配置（队列分配、并发数）在 `horizon["supervisors"]` 中
- 面板路径默认在平台面板下：`/backend/horizon`

## 微信 / 支付宝 / 阿里云

第三方平台配置均通过 `Foundation` 集群的后台界面维护（模型 `Wechat`、`WechatMini`、`WechatPayment`、`Alipay`、`Aliyun` 等），代码中通过对应服务读取：

- `WechatService` / `WechatPaymentService`（EasyWeChat / yansongda）
- `AlipayService`（yansongda）
- `UploadService`（OSS / S3 上传）

环境变量中的 `WECHAT_OFFICIAL_ACCOUNT_*` 等作为全局兜底配置。
