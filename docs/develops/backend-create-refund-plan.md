# 租户侧创建退款单 - 开发计划

## 一、需求分析

在 **Tenant 订单详情页** 和 **订单列表页** 添加"创建退款单"操作，允许租户管理员直接为订单创建退款。

### 当前状态

- 退款创建逻辑已在 `RefundService::createRefund()` 中实现
- Tenant 的 RefundResource 有取消和提交退货物流的功能
- Backend 的 RefundResource 只有查看功能

### 目标

1. 在租户订单操作中添加创建退款单功能
2. 支持选择退款类型、退款原因、退款商品
3. 自动计算退款金额
4. 支持部分退款和全额退款

---

## 二、涉及文件

| 类别 | 文件路径 | 操作 |
|------|----------|------|
| **新建 Action** | `app/Filament/Actions/Mall/CreateRefundAction.php` | 新建 |
| **修改 Table** | `app/Filament/Tenant/Clusters/Mall/Resources/Orders/Tables/OrdersTable.php` | 修改 |
| **修改 Page** | `app/Filament/Tenant/Clusters/Mall/Resources/Orders/Pages/ViewOrder.php` | 修改 |
| **修改 Policy** | `app/Policies/Mall/OrderPolicy.php` | 修改 |
| **引用 Service** | `app/Services/Mall/RefundService.php` | 只读 |
| **引用模型** | `app/Models/Mall/Refund.php` | 只读 |
| **引用枚举** | `app/Enums/Mall/RefundType.php` | 只读 |
| **引用枚举** | `app/Enums/Mall/RefundReason.php` | 只读 |
| **引用枚举** | `app/Enums/Mall/RefundLogAction.php` | 只读 |

---

## 三、功能设计

### 3.1 CreateRefundAction 配置

```
Action 配置：
├── 名称: createRefund
├── 标签: 创建退款单
├── 图标: Heroicon::OutlinedArrowUturnLeft
├── 需要确认: true
├── 模态框标题: 创建退款单
├── 可见条件:
│   ├── 订单状态允许退款（Paid/Preparing/PartiallyShipped/Delivered/Signed/Completed）
│   └── 无进行中的退款单
└── 表单字段:
    ├── Select: refund_type（退款类型）- 仅退款/退货退款
    ├── Select: reason（退款原因）- 根据类型动态显示
    ├── TextInput: reason_detail（原因详情）- 选"其他"时显示
    └── Repeater: items（退款商品）- 选择退款商品和数量
        ├── order_item_id: 订单商品ID
        └── qty: 退款数量（不能超过可退数量）
```

### 3.2 退款类型与原因对应关系

| 退款类型                    | 支持的退款原因                                                                                                  |
|-----------------------------|-----------------------------------------------------------------------------------------------------------------|
| **仅退款** (OnlyRefund)     | 不想要了、拍错了、未收到货、迟迟未发货、质量问题、商品损坏、与描述不符、尺码问题、发错货、少发/漏发、假货、其他 |
| **退货退款** (ReturnRefund) | 不想要了、拍错了、质量问题、商品损坏、与描述不符、尺码问题、发错货、其他                                        |

### 3.3 业务流程

```
创建退款单流程：
1. 验证订单状态是否允许退款
2. 检查是否有进行中的退款单
3. 验证退款类型（已发货订单不能选"仅退款"）
4. 验证退款商品和数量
5. 计算退款金额
6. 调用 RefundService::createRefund()
7. 记录操作日志（RefundLogAction::Created）
```

### 3.4 允许退款的订单状态

| 订单状态         | 说明          | 允许退款类型     |
|------------------|---------------|------------------|
| Paid             | 已支付/待发货 | 仅退款、退货退款 |
| Preparing        | 备货中        | 仅退款、退货退款 |
| PartiallyShipped | 部分发货      | 仅退款、退货退款 |
| Delivered        | 已发货        | 退货退款         |
| Signed           | 已签收        | 退货退款         |
| Completed        | 已完成        | 退货退款         |

### 3.5 退款金额计算

- `goods_amount` = SUM (每项商品的 qty × price)
- `freight_amount` = 当前固定为 0.00
- `total` = goods_amount + freight_amount

### 3.6 可退数量计算

可退数量 = 原订单商品数量 - 该商品已退款数量（不含已取消的退款）

```php
$refundableQty = $orderItem->qty - $orderItem->refundItems()
    ->whereHas('refund', fn ($q) => $q->whereNotIn('status', [
        RefundStatus::Cancelled,
        RefundStatus::Rejected,
    ]))
    ->sum('qty');
```

### 3.7 退款商品选择

订单商品列表应显示以下信息：

| 字段                     | 说明     |
|--------------------------|----------|
| `orderItem.product.name` | 商品名称 |
| `orderItem.sku.name`     | SKU名称  |
| `orderItem.qty`          | 原数量   |
| `orderItem.price`        | 单价     |
| `refundableQty`          | 可退数量 |
| `orderItem.amount`       | 小计金额 |

### 3.8 退款金额实时展示

表单中应实时显示退款金额：

- 选择退款商品和数量后，自动计算并显示退款金额
- 退款金额 = SUM (每项商品的 qty × price)

### 3.9 退款日志记录

创建退款单时自动创建操作日志：

```php
$refund->logs()->create([
    'action' => RefundLogAction::Created,
    'operator_id' => Filament::auth()->id(),
    'remark' => '管理员创建退款单',
    'context' => [
        'refund_type' => $data['refund_type'],
        'reason' => $data['reason'],
    ],
]);
```

### 3.10 错误处理

| 错误场景             | 处理方式           |
|----------------------|--------------------|
| 订单状态不允许退款   | 显示错误提示       |
| 有进行中的退款单     | 显示错误提示       |
| 退款类型不匹配       | 自动调整或显示错误 |
| 退款数量超过可退数量 | 显示错误提示       |
| 退款金额计算错误     | 重新计算           |

### 3.11 通知机制

创建退款单后可能需要通知用户：

- 创建退款单时可选发送通知
- 通知用户退款单已创建，等待审核

---

## 四、开发步骤

### Step 1: 创建 CreateRefundAction

**文件:** `app/Filament/Actions/Mall/CreateRefundAction.php`

```php
// 主要功能：
// 1. 定义表单（退款类型、原因、商品选择）
// 2. 实现业务逻辑（验证 + 调用 Service）
// 3. 权限检查
// 4. 记录操作日志
// 5. 错误处理
```

**表单字段设计:**

| 字段                    | 类型      | 说明                       | 验证规则                     |
|-------------------------|-----------|----------------------------|------------------------------|
| `refund_type`           | Select    | 退款类型：仅退款/退货退款  | required                     |
| `reason`                | Select    | 退款原因：根据类型动态显示 | required                     |
| `reason_detail`         | TextInput | 原因详情：选"其他"时显示   | nullable, max:500            |
| `items`                 | Repeater  | 退款商品列表               | required, min:1              |
| `items.*.order_item_id` | Select    | 订单商品ID                 | required, exists:order_items |
| `items.*.qty`           | TextInput | 退款数量                   | required, integer, min:1     |

**表单联动设计:**

1. `refund_type` 变化时，更新 `reason` 的选项
2. `reason` 为 "Other" 时，显示 `reason_detail` 字段
3. `items` 变化时，实时计算退款金额

### Step 2: 修改 OrdersTable

**文件:** `app/Filament/Tenant/Clusters/Mall/Resources/Orders/Tables/OrdersTable.php`

在 `recordActions` 的 `ActionGroup` 中添加 `CreateRefundAction`。

```php
->recordActions([
    Actions\ViewAction::make(),
    Actions\ActionGroup::make([
        CreateRefundAction::make(),  // 新增
        // ... 其他 action
    ]),
])
```

### Step 3: 修改 ViewOrder

**文件:** `app/Filament/Tenant/Clusters/Mall/Resources/Orders/Pages/ViewOrder.php`

在 `getHeaderActions` 中添加 `CreateRefundAction`。

```php
protected function getHeaderActions(): array
{
    return [
        BackAction::make(),
        CreateRefundAction::make(),  // 新增
    ];
}
```

### Step 4: 修改 OrderPolicy

**文件:** `app/Policies/Mall/OrderPolicy.php`

添加 `createRefund` 权限方法。

```php
#[PolicyName('创建退款单', type: PolicyType::Button)]
public function createRefund(Authenticatable $user, Order $record): bool
{
    return $user->hasPermission(__CLASS__, __FUNCTION__);
}
```

---

## 五、权限设计

| 权限方法 | 说明 | PolicyName |
|----------|------|------------|
| `createRefund` | 创建退款单 | 创建退款单 |

**权限说明：**
- 此权限仅在租户侧使用
- 后台（Backend）不提供创建退款单功能
- 租户管理员可以为订单创建退款单

---

## 六、注意事项

1. **退款类型限制**
    - 未发货订单：可选"仅退款"或"退货退款"
    - 已发货订单：必须选择"退货退款"

2. **退款数量验证**
    - 不能超过可退数量（原数量 - 已退款数量）
    - 已取消/已拒绝的退款不计入已退款数量

3. **退款状态流转**
    - 创建后状态为 `Pending`（待审核）
    - 需要后续审核流程（approveRefund/rejectRefund）

4. **资源回收**
    - 确认退款完成时触发 `Refundable` 契约
    - 实体商品：回退库存
    - 虚拟权益：撤销已授予的身份
    - 充值套餐：扣减账户余额

5. **退款商品验证**
    - 退款商品必须属于当前订单
    - 退款数量必须大于 0
    - 每个订单商品只能退款一次（部分退款可多次，但累计不能超过原数量）

6. **退款金额验证**
    - 退款金额由系统计算，不允许手动修改
    - 退款金额不能超过订单商品金额

---

## 七、测试用例

### 7.1 正常创建退款单

1. 选择一个已支付订单
2. 点击"创建退款单"
3. 选择退款类型和原因
4. 选择退款商品和数量
5. 确认创建
6. 验证退款单创建成功，状态为 Pending

### 7.2 部分退款

1. 选择一个包含多个商品的订单
2. 只选择部分商品进行退款
3. 验证退款金额正确计算

### 7.3 数量验证

1. 尝试退款数量超过可退数量
2. 验证系统提示错误

### 7.4 状态限制

1. 尝试为已完成退款的订单再次创建退款
2. 验证系统提示已有进行中的退款单

### 7.5 退款类型验证

1. 选择已发货订单
2. 尝试选择"仅退款"
3. 验证系统不允许或自动调整

### 7.6 退款金额计算

1. 创建包含多个商品的退款单
2. 验证退款金额 = SUM (每项商品的 qty × price)

### 7.7 操作日志

1. 创建退款单后
2. 验证 RefundLog 中有 Created 记录

---

## 八、后续优化

1. 支持在租户订单列表页批量创建退款单
2. 支持退款单导出
3. 支持退款原因统计分析
4. 支持退款金额手动调整（需要权限控制）
5. 支持退款单撤销（仅 Pending 状态）
6. 支持退款进度查询（用户端）
7. 支持退款原因统计（租户端）

---

## 九、关联文档

- [RefundService API 文档](./refund-service-api.md)
- [退款状态流转图](./refund-status-flow.md)
- [退款权限矩阵](./refund-permissions.md)
