# 退款权限矩阵

## 一、权限列表

| 权限方法 | 权限名称 | 类型 | 说明 |
|----------|----------|------|------|
| `viewAny` | 列表 | Page | 查看退款单列表 |
| `view` | 详情 | Page | 查看退款单详情 |
| `create` | 创建 | Page | 创建退款单 |
| `update` | 编辑 | Page | 编辑退款单 |
| `delete` | 删除 | Button | 删除退款单（仅限 Rejected/Cancelled 状态） |
| `deleteAny` | 批量删除 | Button | 批量删除退款单 |
| `forceDelete` | 永久删除 | Button | 永久删除退款单 |
| `forceDeleteAny` | 批量永久删除 | Button | 批量永久删除退款单 |
| `restore` | 恢复 | Button | 恢复已删除的退款单 |
| `restoreAny` | 批量恢复 | Button | 批量恢复已删除的退款单 |
| `reorder` | 排序 | Button | 调整退款单排序 |
| `disableBulk` | 批量禁用 | Button | 批量禁用退款单 |
| `enableBulk` | 批量启用 | Button | 批量启用退款单 |
| `cancelRefund` | 取消退款 | Button | 取消退款申请 |
| `approveRefund` | 审核通过 | Button | 审核通过退款申请 |
| `rejectRefund` | 审核驳回 | Button | 审核驳回退款申请 |
| `confirmRefund` | 确认退款 | Button | 确认退款完成 |
| `shipReturn` | 提交退货物流 | Button | 提交退货物流信息 |
| `confirmReceive` | 确认签收 | Button | 确认签收退货商品 |

---

## 二、权限与状态关系矩阵

### 2.1 操作权限与退款状态

| 权限 | Pending | WaitingReturn | Shipping | Received | Processing | Completed | Rejected | Cancelled | Failed |
|------|---------|---------------|----------|----------|------------|-----------|----------|-----------|--------|
| `view` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `update` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `delete` | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ |
| `cancelRefund` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `approveRefund` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `rejectRefund` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `confirmRefund` | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `shipReturn` | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `confirmReceive` | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

### 2.2 操作权限与退款类型

| 权限 | 仅退款 | 退货退款 |
|------|--------|----------|
| `approveRefund` | ✅ | ✅ |
| `rejectRefund` | ✅ | ✅ |
| `cancelRefund` | ✅ | ✅ |
| `confirmRefund` | ✅ | ✅ |
| `shipReturn` | ❌ | ✅ |
| `confirmReceive` | ❌ | ✅ |

---

## 三、角色权限矩阵

### 3.1 平台管理员 (Backend)

| 权限 | 说明 | 默认状态 |
|------|------|----------|
| `viewAny` | 查看退款单列表 | ✅ |
| `view` | 查看退款单详情 | ✅ |
| `create` | 创建退款单 | ✅ |
| `update` | 编辑退款单 | ❌ |
| `delete` | 删除退款单 | ❌ |
| `cancelRefund` | 取消退款 | ❌ |
| `approveRefund` | 审核通过 | ❌ |
| `rejectRefund` | 审核驳回 | ❌ |
| `confirmRefund` | 确认退款 | ❌ |
| `shipReturn` | 提交退货物流 | ❌ |
| `confirmReceive` | 确认签收 | ❌ |

### 3.2 租户管理员 (Tenant)

| 权限 | 说明 | 默认状态 |
|------|------|----------|
| `viewAny` | 查看退款单列表 | ✅ |
| `view` | 查看退款单详情 | ✅ |
| `create` | 创建退款单 | ❌ |
| `update` | 编辑退款单 | ❌ |
| `delete` | 删除退款单 | ❌ |
| `cancelRefund` | 取消退款 | ✅ |
| `approveRefund` | 审核通过 | ✅ |
| `rejectRefund` | 审核驳回 | ✅ |
| `confirmRefund` | 确认退款 | ✅ |
| `shipReturn` | 提交退货物流 | ❌ |
| `confirmReceive` | 确认签收 | ✅ |

### 3.3 普通用户

| 权限 | 说明 | 默认状态 |
|------|------|----------|
| `viewAny` | 查看退款单列表 | ❌ |
| `view` | 查看退款单详情 | ✅ |
| `create` | 创建退款单 | ✅ |
| `update` | 编辑退款单 | ❌ |
| `delete` | 删除退款单 | ❌ |
| `cancelRefund` | 取消退款 | ✅ |
| `approveRefund` | 审核通过 | ❌ |
| `rejectRefund` | 审核驳回 | ❌ |
| `confirmRefund` | 确认退款 | ❌ |
| `shipReturn` | 提交退货物流 | ✅ |
| `confirmReceive` | 确认签收 | ❌ |

---

## 四、权限检查逻辑

### 4.1 delete 权限检查

```php
public function delete(Authenticatable $user, Refund $refund): bool
{
    // 仅允许删除 Rejected 或 Cancelled 状态的退款单
    if (!in_array($refund->status, [RefundStatus::Rejected, RefundStatus::Cancelled], true)) {
        return false;
    }

    return $user->hasPermission(__CLASS__, __FUNCTION__);
}
```

### 4.2 操作权限检查

操作权限检查需要同时满足：

1. 用户拥有该权限
2. 退款单状态允许该操作

```php
// 示例：审核通过权限检查
public function approveRefund(Authenticatable $user): bool
{
    return $user->hasPermission(__CLASS__, __FUNCTION__);
}

// 状态检查在 Action 中进行
$refund->status === RefundStatus::Pending
```

---

## 五、权限分配建议

### 5.1 客服角色

```
权限列表：
- viewAny: ✅
- view: ✅
- approveRefund: ✅
- rejectRefund: ✅
- cancelRefund: ✅
```

### 5.2 财务角色

```
权限列表：
- viewAny: ✅
- view: ✅
- confirmRefund: ✅
```

### 5.3 仓储角色

```
权限列表：
- viewAny: ✅
- view: ✅
- confirmReceive: ✅
```

---

## 六、权限配置方式

### 6.1 后台配置

在后台管理界面的角色管理中，可以为不同角色分配退款相关权限。

### 6.2 代码配置

```php
// 在 Role 模型中分配权限
$role->givePermissionTo('refund.approveRefund');
$role->givePermissionTo('refund.rejectRefund');
```

### 6.3 权限命名规则

权限命名格式：`{modelName}.{permissionMethod}`

例如：
- `refund.viewAny`
- `refund.approveRefund`
- `refund.rejectRefund`
