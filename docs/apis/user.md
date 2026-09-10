# User - 用户中心 API

**前缀**: `/user`  
**认证**: 除「公开接口」外，其余接口需要 `auth:sanctum` 中间件

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
            "value": "male",
            "label": "男",
            "color": "danger"
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
            "value": "male",
            "label": "男",
            "color": "danger"
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
                "label": "消费",
                "color": "rose"
            },
            "asset": {
                "value": "balance",
                "label": "余额",
                "color": "primary"
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

### 8. 支付密码设置状态

```
GET /user/safe/payment-password/status
```

查询当前用户是否已设置支付密码，前端据此判断显示"设置支付密码"（POST）还是"修改支付密码"（PUT）。

### 响应

```json
{
    "has_password": false
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| has_password | bool | 是否已设置支付密码（`true`=已设置，调用 PUT 修改；`false`=未设置，调用 POST 设置） |

### 9. 设置支付密码

```
POST /user/safe/payment-password
```

首次设置支付密码。未设置支付密码的用户无法使用余额支付。

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| password | string | 是 | 支付密码（6 位数字，不能为重复或连续数字） |
| re_password | string | 是 | 确认密码（需与 password 一致） |

### 响应

```json
{
    "code": 0,
    "message": "支付密码设置成功"
}
```

### 10. 修改支付密码

```
PUT /user/safe/payment-password
```

修改已设置的支付密码。

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| old_password | string | 是 | 原支付密码 |
| password | string | 是 | 新支付密码（6 位数字，不能为重复或连续数字） |
| re_password | string | 是 | 确认密码（需与 password 一致） |

### 响应

```json
{
    "code": 0,
    "message": "支付密码修改成功"
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

### 21. 发票统计

```
GET /user/invoices/stats
```

返回当前用户的发票统计数据。

### 响应

```json
{
    "total_invoice": 5,
    "title_count": 2,
    "pending_count": 1,
    "completed_count": 3
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| total_invoice | int | 发票总数 |
| title_count | int | 抬头数量 |
| pending_count | int | 申请中数量（待审核 + 已批准） |
| completed_count | int | 已开具数量 |

### 22. 可开票订单列表

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
                "label": "已完成",
                "color": "emerald"
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

### 23. 发票申请列表

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
                "label": "待审核",
                "color": "warning"
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

### 24. 发票申请详情

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
        "label": "待审核",
        "color": "warning"
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
                "label": "已完成",
                "color": "emerald"
            },
            "total_amount": "198.00",
            "paid_at": "2025-01-01 10:00:00",
            "created_at": "2025-01-01 09:00:00"
        }
    ],
    "created_at": "2025-01-01 10:00:00"
}
```

### 25. 提交发票申请

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
        "label": "待审核",
        "color": "warning"
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

### 26. 已开具发票列表

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
                "value": "normal",
                "label": "普通发票",
                "color": "primary"
            },
            "amount": "198.00",
            "status": {
                "value": "issued",
                "label": "已开具",
                "color": "success"
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

### 27. 发票详情

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
        "value": "normal",
        "label": "普通发票",
        "color": "primary"
    },
    "amount": "198.00",
    "status": {
        "value": "issued",
        "label": "已开具",
        "color": "success"
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
            "label": "已批准",
            "color": "success"
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

## 隶属关系管理

**前缀**: `/user/relations`

### 28. 获取下级列表

```
GET /user/relations
```

获取当前用户的直属上级及所有下级（推荐链路，按层级正序排列）。

### 响应

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
| parent.user_id | int | 直属上级用户 ID |
| parent.username | string | 直属上级账号 |
| parent.nickname | string \| null | 直属上级昵称 |
| parent.avatar | string \| null | 直属上级头像 |
| list | array | 下级用户列表 |
| list[].user_id | int | 下级用户 ID |
| list[].username | string | 下级用户账号 |
| list[].nickname | string \| null | 用户昵称 |
| list[].avatar | string \| null | 用户头像 |
| list[].created_at | string | 注册时间（ISO 8601） |
| list[].parent_id | int | 直接上级用户 ID |
| list[].layer | int | 绝对层级 |

未有下级时 `list` 为空数组。

### 29. 绑定上级

```
POST /user/relations/bind/{parentId}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| parentId | int | 上级用户 ID |

### 说明

- 不能绑定自己为上级
- 不能将下级设为上级（防止循环）
- 已有上级时不可重复绑定

### 响应

```json
{
    "code": 0,
    "message": "绑定成功"
}
```

### 30. 数据概览

```
GET /user/relations/overview
```

返回当前用户的推广统计数据。

### 响应

```json
{
    "total_commission": 0,
    "pending_settlement": 0,
    "team_count": 12,
    "promotion_orders": 0
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| total_commission | int | 累积佣金（暂无数据来源，暂返回虚拟数据 0） |
| pending_settlement | int | 待结算（暂无数据来源，暂返回虚拟数据 0） |
| team_count | int | 团队人数（真实数据） |
| promotion_orders | int | 推广订单（暂无数据来源，暂返回虚拟数据 0） |

---

## 身份管理

**前缀**: `/user/identities`

### 31. 当前用户有效身份列表

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

### 32. 可订阅/购买的身份列表

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

### 33. 检查是否持有指定身份

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

### 34. 通知列表

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

### 35. 通知分组列表

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

### 36. 通知详情

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

### 37. 单条标记已读

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

### 38. 全部标记已读

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

### 39. 获取通知数量

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

### 40. 删除全部已读通知

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

### 41. 删除通知

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

---

## 实名认证

**前缀**: `/user/realname`

实名认证用于提现等敏感操作。分为**个人认证**（姓名、身份证号及正反面照片）与**企业认证**（企业名称、营业执照、联系人、联系电话）。

证件照片需先调用图片上传接口（`POST /system/upload/image`），提交其返回的 `path` 字段。证件照属敏感资料，上传时应传 `visibility=private`，文件以私有权限存储，读取时返回短期签名链接。

> 身份证号、证件照属敏感信息，接口返回时身份证号做脱敏处理，请勿在前端明文存储完整号码。证件图片 URL 为临时签名链接，过期后需重新获取。

### 42. 获取当前用户最新实名认证记录

```
GET /user/realname
```

同一用户同一认证类型仅保留一条记录；被拒后可修改资料重新提交（更新原记录）。本接口返回当前用户**最新一条**实名认证记录（即当前认证状态）。

**未提交过认证**时返回：

```json
{
    "code": 0,
    "message": "暂未提交实名认证"
}
```

### 响应

```json
{
    "realname_id": 2,
    "type": "personal",
    "type_label": "个人认证",
    "status": "pending",
    "status_label": "待审核",
    "name": "张三",
    "id_card_number_masked": "1101**********1234",
    "id_card_front": "https://.../storage/2026/09/09/xxx.jpg",
    "id_card_back": "https://.../storage/2026/09/09/xxx.jpg",
    "business_license": null,
    "contact_person": null,
    "contact_phone": null,
    "reject_reason": null,
    "verified_at": null,
    "created_at": "2026-09-09T10:00:00Z"
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| realname_id | int | 认证记录 ID（最新一条） |
| type | string | 认证类型：`personal`、`enterprise` |
| type_label | string | 认证类型名称 |
| status | string | 状态：`pending`、`approved`、`rejected` |
| status_label | string | 状态名称 |
| name | string | 真实姓名/企业名称 |
| id_card_number_masked | string \| null | 身份证号（脱敏，保留前4后4） |
| id_card_front / id_card_back | string \| null | 身份证正反面图 URL（仅个人） |
| business_license | string \| null | 营业执照图 URL（仅企业） |
| contact_person / contact_phone | string \| null | 联系人/电话（仅企业） |
| reject_reason | string \| null | 拒绝原因（被拒时） |
| verified_at | string \| null | 认证通过时间 |

### 43. 获取实名认证状态

```
GET /user/realname/status
```

返回轻量认证状态（不含姓名、证件号、照片等敏感资料），用于页面顶部展示认证状态或提现等场景前的认证校验。

### 响应

统一返回 `{value, label, color}` 结构，未提交时 `value` 为 `null`：

未提交过认证：

```json
{
    "value": null,
    "label": "未提交",
    "color": "gray"
}
```

审核中：

```json
{
    "value": "pending",
    "label": "待审核",
    "color": "warning"
}
```

已通过：

```json
{
    "value": "approved",
    "label": "已认证",
    "color": "success"
}
```

已拒绝：

```json
{
    "value": "rejected",
    "label": "已拒绝",
    "color": "danger"
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| value | string \| null | 状态值：`pending`、`approved`、`rejected`；未提交为 `null` |
| label | string | 状态名称：待审核、已认证、已拒绝、未提交 |
| color | string | 状态颜色：`warning`、`success`、`danger`、`gray` |

### 44. 提交/重新提交实名认证

```
POST /user/realname
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 是 | 认证类型：`personal`（个人）、`enterprise`（企业） |
| name | string | 是 | 真实姓名/企业名称 |
| id_card_number | string | 个人必填 | 身份证号（18位，校验格式与校验码） |
| id_card_front | string | 个人必填 | 身份证正面照（上传接口返回的 `path`） |
| id_card_back | string | 个人必填 | 身份证背面照（上传接口返回的 `path`） |
| business_license | string | 企业必填 | 营业执照（上传接口返回的 `path`） |
| contact_person | string | 企业必填 | 联系人（最大32字符） |
| contact_phone | string | 企业必填 | 联系电话（最大20字符） |

### 响应

与「获取当前用户最新实名认证记录」单条结构一致，提交成功返回 `status = pending`。

### 限制

- 提交后进入待审核，由后台人工审核
- `approved`（已通过）：不可重复申请
- `pending`（审核中）：不可重复提交
- `rejected`（已拒绝）：可修改资料后重新提交，提交后更新原记录为待审核、清空拒绝原因与通过时间
- 同一认证类型仅保留一条记录（数据库 `unique(user_id, type)` 唯一约束兜底）
- 状态流转：`pending → approved / rejected`

### 错误响应

| 场景 | HTTP 状态码 | 示例消息 |
|------|------------|---------|
| 参数验证失败 | 422 | 身份证号格式不正确 / 请先上传身份证正面照 |
| 已通过 | 400 | 「个人认证」已通过认证，不可重复申请 |
| 审核中 | 400 | 实名认证审核中，请勿重复提交 |

---

## 公开接口

无需登录即可访问。

### 45. 获取指定用户公开信息

```
GET /user/{user}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| user | int | 用户 ID |

返回指定用户的公开信息。仅暴露公开字段，不包含账号（`username`）、生日等隐私数据。

### 响应

```json
{
    "user_id": 1,
    "nickname": "Jason",
    "avatar": "https://..."
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| user_id | int | 用户 ID |
| nickname | string \| null | 昵称 |
| avatar | string \| null | 头像 URL |

### 错误响应

用户不存在时返回 404：

```json
{
    "code": 404,
    "message": "请求的资源不存在"
}
```

### 说明

- 无需登录，公开访问
- 仅返回公开字段（`user_id`、`nickname`、`avatar`），不返回登录账号、手机号、生日等隐私信息
