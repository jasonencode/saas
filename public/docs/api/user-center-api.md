# 用户中心 API 文档

Base: `https://{api_domain}`（由环境变量 `API_DOMAIN` 配置），认证方式：`Bearer Token`（Sanctum），全模块需登录。

> 需要租户上下文的请求请附带请求头 `X-Tenant-Id: {tenantId}`（见 [多租户](../core/multi-tenancy)）。
> 路由定义：`routes/apis/user.php`（前缀 `/user`，`auth:sanctum` 中间件）。

---

## 1. 个人资料

### GET /user/profile — 获取个人资料

**响应：**
```json
{
    "user_id": 1,
    "username": "13800138000",
    "profile": {
        "nickname": "用户:8000",
        "avatar": "https://...",
        "gender": { "value": "Male", "label": "男" },
        "birthday": "2000-01-01"
    }
}
```

### PUT /user/profile — 修改个人资料

**输入：**
```json
{
    "nickname": "必填，2-32位",
    "gender": "可选，Gender 枚举 (Male/Female/Secrecy)",
    "birthday": "可选，日期 Y-m-d",
    "avatar": "可选，文件路径（通过上传接口获取）"
}
```

**响应：** 同 GET 响应结构。

---

## 2. 账户

### GET /user/account — 账户信息

**响应：**
```json
{
    "balance": "1000.00",
    "frozen_balance": "0.00",
    "points": 500,
    "frozen_points": 0
}
```

### GET /user/account/logs — 账户变动日志

**响应：** 分页列表，包含余额/积分变动记录。

---

## 3. 安全

### GET /user/safe/records — 登录记录

**响应（分页）：**
```json
{
    "list": [
        {
            "ip": "127.0.0.1",
            "user_agent": "Mozilla/5.0 ...",
            "created_at": "2026-05-12 10:00:00"
        }
    ],
    "page": { "total": 10, "current_page": 1, ... }
}
```

### PUT /user/safe/password — 修改密码

**输入：**
```json
{
    "old_pass": "必填，原密码",
    "new_pass": "必填，6-20位新密码",
    "re_pass": "必填，确认新密码（需与 new_pass 一致）"
}
```

**响应：** `{"code": 0, "message": "密码修改成功"}`

### POST /user/safe/logout — 登出

**响应：** `{"code": 0, "message": "已退出登录"}`

### POST /user/safe/payment-password — 设置支付密码

首次设置支付密码。未设置支付密码的用户无法使用余额支付。

**输入：**
```json
{
    "password": "必填，6-20位支付密码",
    "re_password": "必填，确认密码（需与 password 一致）"
}
```

**响应：** `{"code": 0, "message": "支付密码设置成功"}`

### PUT /user/safe/payment-password — 修改支付密码

**输入：**
```json
{
    "old_password": "必填，原支付密码",
    "password": "必填，6-20位新支付密码",
    "re_password": "必填，确认密码（需与 password 一致）"
}
```

**响应：** `{"code": 0, "message": "支付密码修改成功"}`

---

## 4. 地址管理

### GET /user/addresses — 地址列表

**响应：**
```json
[
    {
        "address_id": 1,
        "name": "张三",
        "mobile": "13800138000",
        "province": { "region_id": 1, "parent_id": 0, "name": "广东省", "level": 1 },
        "city": { "region_id": 10, "parent_id": 1, "name": "广州市", "level": 2 },
        "district": { "region_id": 100, "parent_id": 10, "name": "天河区", "level": 3 },
        "address": "详细地址",
        "is_default": true
    }
]
```

### GET /user/addresses/{id} — 地址详情

**响应：** 单条地址对象，同列表项结构。

### POST /user/addresses — 新增地址

**输入：**
```json
{
    "name": "必填，2-16位收件人",
    "mobile": "必填，11位手机号",
    "province_id": "必填，省份ID",
    "city_id": "必填，城市ID",
    "district_id": "必填，区县ID",
    "address": "必填，2-255位详细地址",
    "is_default": "可选，boolean"
}
```

**限制：** 每个用户最多 20 个地址。

### PUT /user/addresses/{id} — 编辑地址

**输入：** 同新增。**响应：** 更新后的地址对象。

### DELETE /user/addresses/{id} — 删除地址

**响应：** `{"code": 0, "message": "操作成功"}`

### PUT /user/addresses/{id}/default — 设为默认

**响应：** `{"code": 0, "message": "操作成功"}`

### GET /user/addresses/default — 默认收货地址

**响应：** 单条地址对象，同列表项结构；无默认地址时为 `null`。

### GET /user/addresses/regions?parent_id=0&layer=1 — 省市区列表

| 参数 | 说明 |
|---|---|
| parent_id | 父级ID，默认 0（获取省份） |
| layer | 1=平铺；2=含一级 children；3=含两级 children |

**响应（layer=2）：**
```json
[{ "region_id": 1, "parent_id": 0, "name": "广东省", "level": 1, "children": [...] }]
```

**响应（layer=1）：**
```json
[{ "region_id": 1, "parent_id": 0, "name": "广东省", "level": 1 }]
```

---

## 5. 发票抬头

### GET /user/invoice-titles — 列表

**响应：**
```json
[
    {
        "title_id": 1,
        "type": "Corporate",
        "type_label": "企业",
        "name": "某某公司",
        "tax_id": "91440101MA5XXXXXX",
        "is_default": true,
        "created_at": "2026-05-12 10:00:00"
    }
]
```

### GET /user/invoice-titles/{id} — 详情

### POST /user/invoice-titles — 新增

**输入：**
```json
{
    "type": "必填，InvoiceTitleType 枚举 (Corporate/Personal)",
    "name": "必填，2-100位",
    "tax_no": "可选，15-20位数字或大写字母",
    "is_default": "可选，boolean"
}
```

**限制：** 每个用户最多 20 个。

### PUT /user/invoice-titles/{id} — 编辑

**输入：** 同新增（tax_no 可选）。**响应：** 更新后的抬头对象。

### DELETE /user/invoice-titles/{id} — 删除

**响应：** `{"code": 0, "message": "操作成功"}`

### PUT /user/invoice-titles/{id}/default — 设为默认

---

## 6. 发票

### GET /user/invoices/stats — 发票统计

**响应：** 发票数量与金额统计（按状态分组）。

### GET /user/invoices/orders — 可开票订单列表

**响应（分页）：** 可申请的订单列表（`InvoicableOrderResource`）。

### GET /user/invoices — 已开具发票列表

**响应（分页）：** Invoice 模型字段（amount, type, status, invoice_date 等）。

### GET /user/invoices/{id} — 发票详情

**响应：** 含关联 `application.invoiceTitle` 与下载链接。

### GET /user/invoices/applications — 发票申请列表

**响应（分页）：** InvoiceApplication 列表。

### GET /user/invoices/applications/{id} — 发票申请详情

### POST /user/invoices/applications — 提交发票申请

**输入：**
```json
{
    "invoice_title_id": "必填，存在的发票抬头ID",
    "amount": "必填，数字，>=0.01",
    "reason": "必填，最多255字符",
    "remark": "可选",
    "order_ids": "可选，关联订单ID数组"
}
```

---

## 7. 通知

### GET /user/notifications?type=xxx — 通知列表

| 参数 | 说明 |
|---|---|
| type | 可选，通知类名过滤 |

**响应（分页）：**
```json
[
    {
        "notification_id": "uuid",
        "title": "通知标题",
        "type": "OrderPaid",
        "data": { "title": "...", "body": "...", "color": "green", "icon": "...", "iconColor": "...", "status": "success" },
        "read": true,
        "read_at": "2026-05-12 10:00:00",
        "created_at": "2026-05-12 09:59:00"
    }
]
```

### GET /user/notifications/group — 通知分组列表

**响应：**
```json
[
    {
        "title": "订单通知",
        "group": "OrderPaid",
        "total": 10,
        "unread": 2,
        "newest": { "notification_id": "uuid", ... }
    }
]
```

### GET /user/notifications/count?type=xxx — 通知数量

**响应：**
```json
{ "total": 50, "unread": 3 }
```

### GET /user/notifications/{uuid} — 通知详情（自动标记已读）

### PUT /user/notifications/{uuid}/read — 单条标记已读

### PUT /user/notifications/read — 全部标记已读

### DELETE /user/notifications/read — 删除全部已读通知

### DELETE /user/notifications/{uuid} — 删除单条通知

---

## 8. 隶属关系

### GET /user/relations — 下级列表

返回当前用户的直属上级及所有下级（推荐链路）。

**响应：**
```json
{
    "parent": {
        "user_id": 1,
        "username": "admin",
        "nickname": "管理员",
        "avatar": "/images/avatar.jpg"
    },
    "list": [
        {
            "user_id": 2,
            "username": "user2",
            "nickname": "用户2",
            "avatar": "/images/avatar.jpg",
            "created_at": "2025-01-01T00:00:00+08:00",
            "parent_id": 1,
            "layer": 1
        }
    ]
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| parent | object \| null | 直属上级信息（无上级时为 null） |
| list | array | 下级用户列表（字段同原列表项结构） |

未有下级时 `list` 为空数组。

### POST /user/relations/bind/{parentId} — 绑定上级（推荐人）

仅支持首次绑定；若已存在隶属关系返回错误「用户关系已存在」。

| 场景 | 响应 |
|---|---|
| 绑定成功 | `{"code": 0, "message": "绑定成功"}` |
| 绑定自己 | 错误「不能将自己设为推荐人」 |
| 推荐人不存在（无关系记录） | 错误「推荐人不存在」 |
| 已绑定过 | 错误「用户关系已存在」 |

### GET /user/relations/overview — 数据概览

**响应：**
```json
{
    "total_commission": 0,
    "pending_settlement": 0,
    "team_count": 12,
    "promotion_orders": 0
}
```

| 字段 | 说明 | 数据来源 |
|---|---|---|
| total_commission | 累积佣金 | 暂无来源，暂返回虚拟数据 `0` |
| pending_settlement | 待结算 | 暂无来源，暂返回虚拟数据 `0` |
| team_count | 团队人数 | `UserRelation::getTeamStats()` 真实数据 |
| promotion_orders | 推广订单 | 暂无来源，暂返回虚拟数据 `0` |

---

## 9. 身份管理

### GET /user/identities — 当前用户有效身份列表

**响应：** 身份对象列表（`IdentityResource`），含身份名称、生效 / 失效时间等。

### GET /user/identities/available/{tenantId} — 可订阅身份列表

| 参数 | 说明 |
|---|---|
| tenantId | 租户 ID（路径参数） |

**响应：** 该租户下可订阅（`can_subscribe`）且启用（`status`）的身份列表，按 `sort` 倒序。

### GET /user/identities/{identity}/check — 检查是否持有指定身份

**响应：**
```json
{
    "has": true,
    "expiring_soon": false
}
```

| 字段 | 说明 |
|---|---|
| has | 是否持有该身份 |
| expiring_soon | 是否将在 7 天内到期 |
