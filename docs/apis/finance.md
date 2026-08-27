# Finance - 财务 API

**认证**: 全部接口需要 `auth:sanctum` 中间件

**响应格式说明**：
- 错误响应返回 `{"code": 400, "message": "错误信息"}`

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
| gateway | string | 是 | 支付网关：`wechat`（微信）、`alipay`（支付宝）、`balance`（余额）、`manual`（线下） |
| paymentable_type | string | 否 | 关联业务类型（多态） |
| paymentable_id | int | 否 | 关联业务 ID |
| remark | string | 否 | 备注（最大500字） |

### 响应

```json
{
    "order_id": 1,
    "order_no": "PAY20240101000001",
    "amount": "100.00",
    "gateway": "wechat",
    "gateway_label": "微信支付",
    "status": "pending",
    "status_label": "待支付",
    "paymentable_type": "order",
    "paymentable_id": 1,
    "remark": null,
    "paid_at": null,
    "expired_at": "2024-01-01T00:30:00Z",
    "created_at": "2024-01-01T00:00:00Z"
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

### 3. 发起支付

```
POST /payments/{payment}/pay
```

| 参数 | 类型 | 说明 |
|------|------|------|
| payment | int | 支付订单 ID |

### 说明

- 仅待支付状态的订单可发起支付
- 仅支持微信支付（`gateway=wechat`）
- 需要用户已绑定微信账号
- 返回 `prepay_id` 供前端调起微信支付

### 响应

```json
{
    "appId": "wx1234567890abcdef",
    "timeStamp": "1693020931",
    "nonceStr": "5K8264ILTpCH1FNQ2Eyv1psImkmyVB7qfGyv2O8Eh7",
    "package": "prepay_id=wx201410272009395522657a690389285100",
    "signType": "RSA",
    "paySign": "oR9d8PuhnIc+YZ8cBHFCwfgpaK9gd7vaRvkYD7rthRAZ..."
}
```

小程序调用示例：

```javascript
wx.requestPayment({
    timeStamp: res.timeStamp,
    nonceStr: res.nonceStr,
    package: res.package,
    signType: res.signType,
    paySign: res.paySign,
    success() { },
    fail() { }
})
```

### 4. 微信支付回调

```
POST /payments/{payment}/notify
```

| 参数 | 类型 | 说明 |
|------|------|------|
| payment | int | 支付订单 ID |

### 说明

- 无需登录，由微信服务器调用
- 接收微信支付结果通知，更新订单状态

### 响应

```json
{
    "code": "SUCCESS",
    "message": "成功"
}
```

### 5. 申请退款

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
    "refund_id": 1,
    "no": "RF20240101000001",
    "amount": "50.00",
    "reason": "商品质量问题",
    "status": {
        "value": "pending",
        "label": "待处理"
    },
    "refunded_at": null,
    "created_at": "2024-01-01T00:00:00Z"
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
    "list": [
        {
            "id": 1,
            "amount": "100.00",
            "status": "settled",
            "plan": { ... },
            "created_at": "2024-01-01T00:00:00Z"
        }
    ],
    "page": { ... }
}
```
