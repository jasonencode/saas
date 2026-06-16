# User - 用户中心 API

**前缀**: `/user`  
**认证**: 全部接口需要 `auth:sanctum` 中间件

---

## 用户资料

### 1. 获取用户资料

```
GET /user/profile
```

### 响应

```json
{
    "code": 0,
    "message": "操作成功",
    "data": {
        "id": 1,
        "username": "jason",
        "nickname": "Jason",
        "avatar": "https://...",
        "gender": 1,
        "birthday": "1990-01-01",
        "email": "...",
        "mobile": "...",
        "created_at": "2024-01-01T00:00:00Z"
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
| nickname | string | 否 | 昵称 |
| gender | int | 否 | 性别（0=未知, 1=男, 2=女） |
| birthday | string | 否 | 生日（Y-m-d） |
| avatar | string | 否 | 头像 URL |

### 响应

```json
{
    "code": 0,
    "message": "用户信息更新成功",
    "data": { ... }
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
    "code": 0,
    "message": "操作成功",
    "data": {
        "balance": "1000.00",
        "frozen_balance": "0.00",
        "points": 500,
        "frozen_points": 0
    }
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
| limit | int | 否 | 每页条数（默认20） |

---

## 安全设置

### 5. 登录记录

```
GET /user/safe/records
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

```
HTTP 204 No Content
```

### 7. 退出登录

```
POST /user/safe/logout
```

删除当前访问令牌。

### 响应

```
HTTP 204 No Content
```

---

## 地址管理

**前缀**: `/user/addresses`

### 8. 地址列表

```
GET /user/addresses
```

### 9. 地址详情

```
GET /user/addresses/{address}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| address | int | 地址 ID |

### 10. 获取省市区列表

```
GET /user/addresses/regions
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| parent_id | int | 否 | 上级区域 ID（默认 0=顶级） |
| layer | int | 否 | 返回层级（1=一级, 2=二级含下级数量, 默认1） |

### 11. 新增地址

```
POST /user/addresses
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 是 | 收件人姓名 |
| mobile | string | 是 | 收件人手机号 |
| province_id | int | 是 | 省份 ID |
| city_id | int | 是 | 城市 ID |
| district_id | int | 是 | 区县 ID |
| address | string | 是 | 详细地址 |
| is_default | bool | 否 | 是否设为默认地址 |

### 限制

- 每个用户最多创建 20 个地址

### 12. 编辑地址

```
PUT /user/addresses/{address}
```

### 13. 删除地址

```
DELETE /user/addresses/{address}
```

### 14. 设置默认地址

```
PUT /user/addresses/{address}/default
```

---

## 发票抬头管理

**前缀**: `/user/invoice-titles`

### 15. 发票抬头列表

```
GET /user/invoice-titles
```

### 16. 发票抬头详情

```
GET /user/invoice-titles/{invoiceTitle}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| invoiceTitle | int | 发票抬头 ID |

### 17. 新增发票抬头

```
POST /user/invoice-titles
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 是 | 抬头类型（个人/企业） |
| name | string | 是 | 发票抬头名称 |
| tax_no | string | 否 | 税号（企业类型必填） |
| is_default | bool | 否 | 是否设为默认抬头 |

### 限制

- 每个用户最多创建 20 个发票抬头

### 18. 编辑发票抬头

```
PUT /user/invoice-titles/{invoiceTitle}
```

### 19. 删除发票抬头

```
DELETE /user/invoice-titles/{invoiceTitle}
```

### 20. 设置默认发票抬头

```
PUT /user/invoice-titles/{invoiceTitle}/default
```

---

## 发票管理

**前缀**: `/user/invoices`

### 21. 发票申请列表

```
GET /user/invoices/applications
```

### 22. 发票申请详情

```
GET /user/invoices/applications/{application}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| application | int | 申请 ID |

### 23. 提交发票申请

```
POST /user/invoices/applications
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| invoice_title_id | int | 是 | 发票抬头 ID |
| amount | decimal | 是 | 开票金额 |
| reason | string | 是 | 开票事由 |
| remark | string | 否 | 备注 |
| order_ids | array | 否 | 关联订单 ID 列表 |

### 24. 已开具发票列表

```
GET /user/invoices
```

### 25. 发票详情

```
GET /user/invoices/{invoice}
```

---

## 通知管理

**前缀**: `/user/notifications`

### 26. 通知列表

```
GET /user/notifications
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 否 | 通知类型（类名） |

### 27. 通知分组列表

```
GET /user/notifications/group
```

按类型分组，返回各类型通知数量。

### 28. 通知详情

```
GET /user/notifications/{notification}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| notification | uuid | 通知 UUID |

查看详情时会自动标记为已读。

### 29. 单条标记已读

```
PUT /user/notifications/{notification}/read
```

### 30. 全部标记已读

```
PUT /user/notifications/read
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 否 | 通知类型，仅标记指定类型 |

### 31. 获取通知数量

```
GET /user/notifications/count
```

### 响应

```json
{
    "code": 0,
    "message": "操作成功",
    "data": {
        "total": 100,
        "unread": 5
    }
}
```

### 32. 删除全部已读通知

```
DELETE /user/notifications/read
```

### 33. 删除通知

```
DELETE /user/notifications/{notification}
```
