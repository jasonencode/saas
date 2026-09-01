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
| paymentable_type | string | 否 | 关联业务类型（多态），传简短标识，如 `order`（商城订单） |
| paymentable_id | int | 否 | 关联业务 ID |

> 支持的 `paymentable_type` 白名单：`order`（商城订单）。传入不在白名单内的标识将校验失败（422）。

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
| payment_password | string | 支付密码（`gateway=balance` 余额支付时必填） |

### 说明

- 仅待支付状态的订单可发起支付，单入口按 `gateway` 分流
- 微信支付（`gateway=wechat`）：需用户已绑定微信账号，返回 `prepay_id` 供前端调起微信支付
- 余额支付（`gateway=balance`）：校验支付密码后从用户余额扣除，标记支付单为已支付，并同步推进关联订单状态
- 余额支付关联订单时按**订单应付总额（含运费）**扣款，不信任创建支付单时传入的 `amount`，防止低价买单
- 余额支付错误提示：未设置支付密码（`使用余额支付前，请先设置支付密码`）、支付密码错误、余额不足

### 响应（微信支付）

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

### 响应（余额支付）

余额支付同步完成，直接返回更新后的支付单：

```json
{
    "order_id": 1,
    "order_no": "PAY20240101000001",
    "amount": "100.00",
    "gateway": "balance",
    "gateway_label": "余额支付",
    "status": "paid",
    "status_label": "已支付",
    "paymentable_type": "order",
    "paymentable_id": 1,
    "paid_at": "2024-01-01T00:01:00Z",
    "expired_at": "2024-01-01T00:30:00Z",
    "created_at": "2024-01-01T00:00:00Z"
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
- 接收微信支付结果通知，标记支付单为已支付
- 注意：当前回调仅更新支付单状态，不推进关联业务（订单）状态

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
