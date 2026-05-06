# 用户管理

## 用户模型

系统使用 `App\Models\User` 作为用户模型。

## 用户角色

| 角色 | 说明 |
|------|------|
| Administrator | 超级管理员，拥有所有权限 |
| Tenant | 租户管理员，管理租户内的用户 |
| User | 普通用户 |

## 管理员识别

```php
$user->isAdministrator(): bool
```

## 实名认证

用户实名认证状态：

| 状态 | 说明 |
|------|------|
| Pending | 待审核 |
| Approved | 已通过 |
| Rejected | 已拒绝 |

### 审核操作

```php
// 审核通过
$userRealname->approve($admin);

// 拒绝申请
$userRealname->reject($admin);
```
