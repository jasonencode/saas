# 用户管理

## 用户模型

系统使用 `App\Models\User\User` 作为用户模型：

- 继承 `App\Contracts\Authenticatable`（实现 `HasAvatar`、`HasName` 等 Filament 契约）
- 使用 `HasApiTokens`（Sanctum）、`SoftDeletes`、`Favoriter`（商品收藏）trait
- 通过 `#[UsePolicy(UserPolicy::class)]` 绑定 `UserPolicy`
- 创建用户时自动创建 `UserProfile`（昵称默认「用户:xxxx」）和 `UserAccount`
- 创建事件：`created` => `UserCreatedEvent`

主要关联：

| 关联方法 | 类型 | 关联模型 | 说明 |
|---------|------|---------|------|
| `profile()` | HasOne | `UserProfile` | 用户资料 |
| `account()` | HasOne | `UserAccount` | 用户账户 |
| `relation()` | HasOne | `UserRelation` | 推荐关系（withDefault） |
| `tenants()` | BelongsToMany | `Tenant` | 所属租户（`user_tenant`） |
| `identities()` | BelongsToMany | `Identity` | 用户身份（含 start_at / end_at 时间窗） |
| `addresses()` | HasMany | `Address` | 用户地址 |
| `realname()` | HasOne | `UserRealname` | 实名认证 |
| `invoiceTitles()` | HasMany | `InvoiceTitle` | 发票抬头 |

## 用户角色

| 角色 | 说明 |
|------|------|
| Administrator | 平台管理员，`isAdministrator()` 为真，权限策略直接放行 |
| 租户管理员 | 通过 `AdministratorTenant` 关联租户，管理租户内业务 |
| User | 普通用户，通过 `user_tenant` 关联到租户 |

## 管理员识别

```php
// App\Models\System\Administrator
$user->isAdministrator(): bool
```

平台管理员登录 `/backend`，租户管理员登录 `/tenant`（`auth:tenant` guard）。

## 实名认证

用户实名认证状态（`App\Enums\User\RealnameStatus`）：

| 状态 | 说明 |
|------|------|
| `Pending` | 待审核 |
| `Approved` | 已认证 |
| `Rejected` | 已拒绝 |

### 审核操作

```php
// App\Services\User\RealnameService
service(RealnameService::class)->approve($userRealname);
service(RealnameService::class)->reject($userRealname, '拒绝原因');
```

## 推荐关系（隶属关系）

`UserRelation` 记录用户的推荐链路（上级 / 下级）：

- `parent_id`：直属上级（推荐人）
- 用户首次绑定时写入，`path_prefix` 维护链路前缀（支持按链路统计团队）
- 后台提供用户关系树展示，API 见 `GET /user/relations`

## 用户身份

`Identity` 是租户发布、用户订阅的身份（如会员等级）：

- `Identity` 定义在租户下（`tenant_id`、`can_subscribe`、`status`、`sort`）
- `UserIdentity` 为关联表，pivot 含 `start_at` / `end_at` / `serial` / `tenant_id`
- 订单支付等事件可自动授予身份（`GrantIdentityOnOrderPaid` 监听器）
- 身份到期由 `IdentityExpireCommand` 定时处理
