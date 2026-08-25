# User - 用户中心 API

**前缀**: `/user`  
**认证**: 全部接口需要 `auth:sanctum` 中间件

**响应格式说明**：
- 错误响应返回 `{"code": 400, "message": "错误信息"}`
- 无内容响应（如删除、更新成功）返回 `{"code": 0, "message": "操作成功"}`

---

## 用户资料

### 1. 获取用户资料

```
GET /user/profile
```

### 响应

```json
{
    "user_id": 1,
    "username": "jason",
    "profile": {
        "nickname": "Jason",
        "avatar": "https://...",
        "gender": {
            "value": 1,
            "label": "男"
        },
        "birthday": "1990-01-01"
    }
}
```

### 2. 修改用户资料

```
PUT /user/profile
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| nickname | string | 是 | 昵称（2-32 字符） |
| gender | int | 否 | 性别（1=男, 2=女） |
| birthday | string | 否 | 生日（Y-m-d） |
| avatar | string | 否 | 头像文件标识 |

### 响应

```json
{
    "user_id": 1,
    "username": "jason",
    "profile": {
        "nickname": "Jason",
        "avatar": "https://...",
        "gender": {
            "value": 1,
            "label": "男"
        },
        "birthday": "1990-01-01"
    }
}
```

---

## 账户信息

### 3. 获取账户信息

```
GET /user/account
```

### 响应

```json
{
    "balance": "1000.00",
    "frozen_balance": "0.00",
    "points": 500,
    "frozen_points": 0
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| balance | string | 可用余额 |
| frozen_balance | string | 冻结余额 |
| points | int | 可用积分 |
| frozen_points | int | 冻结积分 |

### 4. 账户变动日志

```
GET /user/account/logs
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | 否 | 页码 |
| per_page | int | 否 | 每页条数（受 `custom.pagination.max_per_page` 限制） |

### 响应

```json
{
    "list": [
        {
            "log_id": 1,
            "type": {
                "value": "consume",
                "label": "消费"
            },
            "asset": {
                "value": "balance",
                "label": "余额"
            },
            "amount": "-99.00",
            "before": "1000.00",
            "after": "901.00",
            "remark": "订单消费",
            "created_at": "2025-01-01 10:00:00"
        }
    ],
    "page": {
        "total": 100,
        "per_page": 20,
        "current_page": 1,
        "last_page": 5
    }
}
```

---

## 安全设置

### 5. 登录记录

```
GET /user/safe/records
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | 否 | 页码 |
| per_page | int | 否 | 每页条数（受 `custom.pagination.max_per_page` 限制） |

### 响应

```json
{
    "list": [
        {
            "ip": "192.168.1.1",
            "user_agent": "Mozilla/5.0...",
            "created_at": "2025-01-01 10:00:00"
        }
    ],
    "page": {
        "total": 50,
        "per_page": 20,
        "current_page": 1,
        "last_page": 3
    }
}
```

### 6. 修改密码

```
PUT /user/safe/password
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| old_pass | string | 是 | 原密码 |
| new_pass | string | 是 | 新密码 |
| new_pass_confirmation | string | 是 | 确认新密码 |

### 响应

```json
{
    "code": 0,
    "message": "密码修改成功"
}
```

### 7. 退出登录

```
POST /user/safe/logout
```

删除当前访问令牌。

### 响应

```json
{
    "code": 0,
    "message": "已退出登录"
}
```

---

## 地址管理

**前缀**: `/user/addresses`

### 8. 地址列表

```
GET /user/addresses
```

### 响应

```json
[
    {
        "address_id": 1,
        "name": "张三",
        "mobile": "13800138000",
        "province": { "id": 1, "name": "广东省" },
        "city": { "id": 2, "name": "深圳市" },
        "district": { "id": 3, "name": "南山区" },
        "address": "详细地址",
        "is_default": true
    }
]
```

### 9. 默认收货地址

```
GET /user/addresses/default
```

获取当前用户的默认收货地址，按 `is_default` 降序 + 最新创建时间排序，确保获取到有效的默认地址。

### 响应

```json
{
    "address_id": 1,
    "name": "张三",
    "mobile": "13800138000",
    "province": { "id": 1, "name": "广东省" },
    "city": { "id": 2, "name": "深圳市" },
    "district": { "id": 3, "name": "南山区" },
    "address": "详细地址",
    "is_default": true
}
```

### 10. 地址详情

```
GET /user/addresses/{address}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| address | int | 地址 ID |

### 响应

同「地址列表」中的单个对象格式。

### 11. 获取省市区列表

```
GET /user/addresses/regions
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| parent_id | int | 否 | 上级区域 ID（默认 0=顶级） |
| layer | int | 否 | 返回层级（1=一级, 2=二级含下级, 3=三级含下级, 默认1） |

### 响应

**layer=1**：
```json
[
    {
        "region_id": 1,
        "parent_id": 0,
        "name": "广东省",
        "level": 1
    }
]
```

**layer=2**：
```json
[
    {
        "region_id": 2,
        "parent_id": 1,
        "name": "深圳市",
        "level": 2,
        "children": [
            {
                "region_id": 3,
                "parent_id": 2,
                "name": "南山区",
                "level": 3
            }
        ]
    }
]
```

**layer=3**：
```json
[
    {
        "region_id": 1,
        "parent_id": 0,
        "name": "广东省",
        "level": 1,
        "children": [
            {
                "region_id": 2,
                "parent_id": 1,
                "name": "深圳市",
                "level": 2,
                "children": [
                    {
                        "region_id": 3,
                        "parent_id": 2,
                        "name": "南山区",
                        "level": 3
                    }
                ]
            }
        ]
    }
]
```

### 12. 新增地址

```
POST /user/addresses
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 是 | 收件人姓名 |
| mobile | string | 是 | 收件人手机号 |
| province | string | 是 | 省份名称（如"广东省"） |
| city | string | 是 | 城市名称（如"深圳市"） |
| district | string | 是 | 区县名称（如"南山区"） |
| address | string | 是 | 详细地址 |
| is_default | bool | 否 | 是否设为默认地址 |

### 限制

- 每个用户最多创建 20 个地址

### 响应

```json
{
    "address_id": 1,
    "name": "张三",
    "mobile": "13800138000",
    "province": { "id": 1, "name": "广东省" },
    "city": { "id": 2, "name": "深圳市" },
    "district": { "id": 3, "name": "南山区" },
    "address": "详细地址",
    "is_default": false
}
```

### 13. 编辑地址

```
PUT /user/addresses/{address}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| address | int | 地址 ID |

### 请求参数

同「新增地址」。

### 响应

同「新增地址」。

### 14. 删除地址

```
DELETE /user/addresses/{address}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| address | int | 地址 ID |

### 响应

```json
{
    "code": 0,
    "message": "删除成功"
}
```

### 15. 设置默认地址

```
PUT /user/addresses/{address}/default
```

| 参数 | 类型 | 说明 |
|------|------|------|
| address | int | 地址 ID |

### 响应

```json
{
    "code": 0,
    "message": "设置成功"
}
```

---

## 发票抬头管理

**前缀**: `/user/invoice-titles`

### 15. 发票抬头列表

```
GET /user/invoice-titles
```

### 响应

```json
[
    {
        "title_id": 1,
        "type": "personal",
        "type_label": "个人",
        "name": "张三",
        "tax_no": null,
        "is_default": true,
        "created_at": "2025-01-01 10:00:00"
    }
]
```

### 16. 发票抬头详情

```
GET /user/invoice-titles/{invoiceTitle}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| invoiceTitle | int | 发票抬头 ID |

### 响应

同「发票抬头列表」中的单个对象格式。

### 17. 新增发票抬头

```
POST /user/invoice-titles
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 是 | 抬头类型：`personal`（个人）、`enterprise`（企业） |
| name | string | 是 | 发票抬头名称（2-100 字符） |
| tax_no | string | 条件 | 税号（企业类型必填，15-20 位数字或大写字母） |
| is_default | bool | 否 | 是否设为默认抬头 |

### 限制

- 每个用户最多创建 20 个发票抬头

### 响应

```json
{
    "title_id": 1,
    "type": "enterprise",
    "type_label": "企业",
    "name": "深圳科技有限公司",
    "tax_no": "91440300XXXXXXXXXX",
    "is_default": false,
    "created_at": "2025-01-01 10:00:00"
}
```

### 18. 编辑发票抬头

```
PUT /user/invoice-titles/{invoiceTitle}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| invoiceTitle | int | 发票抬头 ID |

### 请求参数

同「新增发票抬头」（不含 `is_default`）。

### 响应

同「新增发票抬头」。

### 19. 删除发票抬头

```
DELETE /user/invoice-titles/{invoiceTitle}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| invoiceTitle | int | 发票抬头 ID |

### 响应

```json
{
    "code": 0,
    "message": "删除成功"
}
```

### 20. 设置默认发票抬头

```
PUT /user/invoice-titles/{invoiceTitle}/default
```

| 参数 | 类型 | 说明 |
|------|------|------|
| invoiceTitle | int | 发票抬头 ID |

### 响应

```json
{
    "code": 0,
    "message": "设置成功"
}
```

---

## 发票管理

**前缀**: `/user/invoices`

### 21. 可开票订单列表

```
GET /user/invoices/orders
```

返回可开票的订单列表（已支付且未被其他待处理/已批准发票申请关联）。

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | 否 | 页码 |
| per_page | int | 否 | 每页条数（受 `custom.pagination.max_per_page` 限制） |

### 响应

```json
{
    "list": [
        {
            "order_id": 1,
            "no": "202501010001",
            "status": {
                "value": "completed",
                "label": "已完成"
            },
            "total_amount": "198.00",
            "paid_at": "2025-01-01 10:00:00",
            "created_at": "2025-01-01 09:00:00"
        }
    ],
    "page": {
        "total": 10,
        "per_page": 20,
        "current_page": 1,
        "last_page": 1
    }
}
```

### 22. 发票申请列表

```
GET /user/invoices/applications
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | 否 | 页码 |
| per_page | int | 否 | 每页条数（受 `custom.pagination.max_per_page` 限制） |

### 响应

```json
{
    "list": [
        {
            "application_id": 1,
            "amount": "198.00",
            "reason": "公司报销",
            "remark": "",
            "status": {
                "value": "pending",
                "label": "待审核"
            },
            "invoice_title": {
                "title_id": 1,
                "type": "enterprise",
                "type_label": "企业",
                "name": "深圳科技有限公司",
                "tax_no": "91440300XXXXXXXXXX",
                "is_default": true,
                "created_at": "2025-01-01 10:00:00"
            },
            "orders": [],
            "created_at": "2025-01-01 10:00:00"
        }
    ],
    "page": {
        "total": 5,
        "per_page": 20,
        "current_page": 1,
        "last_page": 1
    }
}
```

### 23. 发票申请详情

```
GET /user/invoices/applications/{application}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| application | int | 申请 ID |

### 响应

```json
{
    "application_id": 1,
    "amount": "198.00",
    "reason": "公司报销",
    "remark": "",
    "status": {
        "value": "pending",
        "label": "待审核"
    },
    "invoice_title": {
        "title_id": 1,
        "type": "enterprise",
        "type_label": "企业",
        "name": "深圳科技有限公司",
        "tax_no": "91440300XXXXXXXXXX",
        "is_default": true,
        "created_at": "2025-01-01 10:00:00"
    },
    "orders": [
        {
            "order_id": 1,
            "no": "202501010001",
            "status": {
                "value": "completed",
                "label": "已完成"
            },
            "total_amount": "198.00",
            "paid_at": "2025-01-01 10:00:00",
            "created_at": "2025-01-01 09:00:00"
        }
    ],
    "created_at": "2025-01-01 10:00:00"
}
```

### 24. 提交发票申请

```
POST /user/invoices/applications
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| invoice_title_id | int | 是 | 发票抬头 ID |
| reason | string | 是 | 开票事由（最长 255 字符） |
| remark | string | 否 | 备注 |
| order_ids | array | 否 | 关联订单 ID 列表 |

### 响应

```json
{
    "application_id": 1,
    "amount": "198.00",
    "reason": "公司报销",
    "remark": "",
    "status": {
        "value": "pending",
        "label": "待审核"
    },
    "invoice_title": {
        "title_id": 1,
        "type": "enterprise",
        "type_label": "企业",
        "name": "深圳科技有限公司",
        "tax_no": "91440300XXXXXXXXXX",
        "is_default": true,
        "created_at": "2025-01-01 10:00:00"
    },
    "orders": [],
    "created_at": "2025-01-01 10:00:00"
}
```

### 25. 已开具发票列表

```
GET /user/invoices
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | 否 | 页码 |
| per_page | int | 否 | 每页条数（受 `custom.pagination.max_per_page` 限制） |

### 响应

```json
{
    "list": [
        {
            "invoice_id": 1,
            "invoice_no": "INV20250101001",
            "invoice_date": "2025-01-05",
            "type": {
                "value": "electronic",
                "label": "电子发票"
            },
            "amount": "198.00",
            "status": {
                "value": "issued",
                "label": "已开具"
            },
            "recipient_email": "user@example.com",
            "recipient_phone": "13800138000",
            "remark": "",
            "creator": "系统",
            "created_at": "2025-01-05 10:00:00"
        }
    ],
    "page": {
        "total": 5,
        "per_page": 20,
        "current_page": 1,
        "last_page": 1
    }
}
```

### 26. 发票详情

```
GET /user/invoices/{invoice}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| invoice | int | 发票 ID |

### 响应

```json
{
    "invoice_id": 1,
    "invoice_no": "INV20250101001",
    "invoice_date": "2025-01-05",
    "type": {
        "value": "electronic",
        "label": "电子发票"
    },
    "amount": "198.00",
    "status": {
        "value": "issued",
        "label": "已开具"
    },
    "recipient_email": "user@example.com",
    "recipient_phone": "13800138000",
    "remark": "",
    "creator": "系统",
    "application": {
        "application_id": 1,
        "amount": "198.00",
        "reason": "公司报销",
        "remark": "",
        "status": {
            "value": "approved",
            "label": "已批准"
        },
        "invoice_title": {
            "title_id": 1,
            "type": "enterprise",
            "type_label": "企业",
            "name": "深圳科技有限公司",
            "tax_no": "91440300XXXXXXXXXX",
            "is_default": true,
            "created_at": "2025-01-01 10:00:00"
        },
        "orders": [],
        "created_at": "2025-01-01 10:00:00"
    },
    "created_at": "2025-01-05 10:00:00"
}
```

---

## 身份管理

**前缀**: `/user/identities`

### 27. 当前用户有效身份列表

```
GET /user/identities
```

### 响应

```json
[
    {
        "identity_id": 1,
        "name": "VIP 会员",
        "description": "尊享会员权益",
        "cover": "https://...",
        "price": "99.00",
        "days": 30,
        "can_subscribe": true,
        "is_unique": false,
        "conditions": null,
        "rules": null,
        "pivot": {
            "start_at": "2025-01-01 00:00:00",
            "end_at": "2025-02-01 00:00:00",
            "serial": "NO20250101001"
        },
        "created_at": "2025-01-01 10:00:00"
    }
]
```

### 28. 可订阅/购买的身份列表

```
GET /user/identities/available/{tenantId}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| tenantId | int | 租户 ID |

### 响应

```json
[
    {
        "identity_id": 1,
        "name": "VIP 会员",
        "description": "尊享会员权益",
        "cover": "https://...",
        "price": "99.00",
        "days": 30,
        "can_subscribe": true,
        "is_unique": false,
        "conditions": null,
        "rules": null,
        "created_at": "2025-01-01 10:00:00"
    }
]
```

### 29. 检查是否持有指定身份

```
GET /user/identities/{identity}/check
```

| 参数 | 类型 | 说明 |
|------|------|------|
| identity | int | 身份 ID |

### 响应

```json
{
    "has": true,
    "expiring_soon": false
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| has | bool | 是否持有该身份 |
| expiring_soon | bool | 是否即将过期（7 天内） |

---

## 通知管理

**前缀**: `/user/notifications`

### 30. 通知列表

```
GET /user/notifications
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 否 | 通知类型（类名） |
| page | int | 否 | 页码 |
| per_page | int | 否 | 每页条数（受 `custom.pagination.max_per_page` 限制） |

### 响应

```json
{
    "list": [
        {
            "notification_id": "550e8400-e29b-41d4-a716-446655440000",
            "title": "订单状态更新",
            "type": "OrderStatusNotification",
            "data": {
                "title": "订单状态更新",
                "body": "您的订单已发货",
                "color": "success",
                "icon": "check-circle",
                "iconColor": "white",
                "status": "shipped"
            },
            "read": false,
            "read_at": null,
            "created_at": "2025-01-01 10:00:00"
        }
    ],
    "page": {
        "total": 100,
        "per_page": 20,
        "current_page": 1,
        "last_page": 5
    }
}
```

### 31. 通知分组列表

```
GET /user/notifications/group
```

按类型分组，返回各类型通知数量。

### 响应

```json
[
    {
        "title": "订单通知",
        "group": "OrderStatusNotification",
        "total": 50,
        "unread": 5,
        "newest": {
            "notification_id": "550e8400-e29b-41d4-a716-446655440000",
            "title": "订单状态更新",
            "type": "OrderStatusNotification",
            "data": {
                "title": "订单状态更新",
                "body": "您的订单已发货",
                "color": "success",
                "icon": "check-circle",
                "iconColor": "white",
                "status": "shipped"
            },
            "read": false,
            "read_at": null,
            "created_at": "2025-01-01 10:00:00"
        }
    }
]
```

### 32. 通知详情

```
GET /user/notifications/{notification}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| notification | uuid | 通知 UUID |

查看详情时会自动标记为已读。

### 响应

```json
{
    "notification_id": "550e8400-e29b-41d4-a716-446655440000",
    "title": "订单状态更新",
    "type": "OrderStatusNotification",
    "data": {
        "title": "订单状态更新",
        "body": "您的订单已发货",
        "color": "success",
        "icon": "check-circle",
        "iconColor": "white",
        "status": "shipped"
    },
    "read": true,
    "read_at": "2025-01-01 11:00:00",
    "created_at": "2025-01-01 10:00:00"
}
```

### 33. 单条标记已读

```
PUT /user/notifications/{notification}/read
```

| 参数 | 类型 | 说明 |
|------|------|------|
| notification | uuid | 通知 UUID |

### 响应

```json
{
    "code": 0,
    "message": "通知已标记为已读"
}
```

### 34. 全部标记已读

```
PUT /user/notifications/read
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 否 | 通知类型，仅标记指定类型 |

### 响应

```json
{
    "code": 0,
    "message": "所有通知已标记为已读"
}
```

### 35. 获取通知数量

```
GET /user/notifications/count
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 否 | 通知类型，仅统计指定类型 |

### 响应

```json
{
    "total": 100,
    "unread": 5
}
```

### 36. 删除全部已读通知

```
DELETE /user/notifications/read
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 否 | 通知类型，仅删除指定类型 |

### 响应

```json
{
    "code": 0,
    "message": "已删除所有已读通知"
}
```

### 37. 删除通知

```
DELETE /user/notifications/{notification}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| notification | uuid | 通知 UUID |

### 响应

```json
{
    "code": 0,
    "message": "通知删除成功"
}
```
