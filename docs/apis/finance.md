# Finance - 财务 API

**认证**: 全部接口需要 `auth:sanctum` 中间件

---

## 支付

**前缀**: `/payments`

### 1. 发起支付

```
POST /payments
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| amount | decimal | 是 | 支付金额（≥0.01） |
| gateway | string | 是 | 支付网关（枚举值） |
| paymentable_type | string | 否 | 关联业务类型 |
| paymentable_id | int | 否 | 关联业务 ID |
| remark | string | 否 | 备注（最大500字） |

### 响应

```json
{
    "code": 0,
    "message": "创建成功",
    "data": {
        "id": 1,
        "amount": "100.00",
        "gateway": "wechat",
        "status": "pending",
        "status_label": "待支付",
        "expired_at": "2024-01-01T00:30:00Z",
        "payment_url": "...",
        "created_at": "2024-01-01T00:00:00Z"
    }
}
```

支付订单默认30分钟后过期。

### 2. 查询支付状态

```
GET /payments/{payment}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| payment | int | 支付订单 ID |

### 3. 申请退款

```
POST /payments/{payment}/refund
```

| 参数 | 类型 | 说明 |
|------|------|------|
| payment | int | 支付订单 ID |

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| amount | decimal | 是 | 退款金额（≤支付金额） |
| reason | string | 是 | 退款原因（最大1000字） |

### 响应

```json
{
    "code": 0,
    "message": "创建成功",
    "data": {
        "refund_id": 1,
        "amount": "50.00",
        "status": "pending",
        "status_label": "待处理"
    }
}
```

仅已支付的订单可申请退款。

---

## 结算凭据

**前缀**: `/vouchers`

### 4. 结算凭据列表

```
GET /vouchers
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| per_page | int | 否 | 每页条数（默认15，最大50） |

### 响应

```json
{
    "code": 0,
    "message": "操作成功",
    "data": [
        {
            "id": 1,
            "amount": "100.00",
            "status": "settled",
            "plan": { ... },
            "created_at": "2024-01-01T00:00:00Z"
        }
    ],
    "meta": { ... }
}
```
