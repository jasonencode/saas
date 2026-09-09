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
        "label": "待审核",
        "color": "amber"
    },
    "refunded_at": null,
    "created_at": "2024-01-01T00:00:00Z"
}
```

仅已支付的订单可申请退款。

---

## 充值

**前缀**: `/recharge`

### 1. 创建充值订单

```
POST /recharge
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| amount | decimal | 是 | 充值金额（≥0.01） |
| type | string | 是 | 充值类型：`balance`（余额充值）、`points`（积分充值） |
| gateway | string | 是 | 支付网关：`wechat`（微信）、`alipay`（支付宝）、`balance`（余额）、`manual`（线下） |
| remark | string | 否 | 备注（最大255字） |

### 响应

```json
{
    "order_id": 1,
    "order_no": "RC20240101000001",
    "type": "balance",
    "type_label": "余额充值",
    "amount": "100.00",
    "received_amount": "100.00",
    "gateway": "wechat",
    "gateway_label": "微信支付",
    "status": "pending",
    "status_label": "待支付",
    "payment_no": null,
    "remark": null,
    "paid_at": null,
    "completed_at": null,
    "expired_at": "2024-01-01T00:30:00Z",
    "created_at": "2024-01-01T00:00:00Z"
}
```

充值订单默认30分钟后过期。

### 2. 查询充值订单状态

```
GET /recharge/{order}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| order | int | 充值订单 ID |

### 响应

```json
{
    "order_id": 1,
    "order_no": "RC20240101000001",
    "type": "balance",
    "type_label": "余额充值",
    "amount": "100.00",
    "received_amount": "100.00",
    "gateway": "wechat",
    "gateway_label": "微信支付",
    "status": "paid",
    "status_label": "已支付",
    "payment_no": "WX20240101000001",
    "remark": null,
    "paid_at": "2024-01-01T00:01:00Z",
    "completed_at": null,
    "expired_at": "2024-01-01T00:30:00Z",
    "created_at": "2024-01-01T00:00:00Z"
}
```

### 3. 取消充值订单

```
POST /recharge/{order}/cancel
```

| 参数 | 类型 | 说明 |
|------|------|------|
| order | int | 充值订单 ID |

### 说明

- 仅待支付或处理中状态的订单可取消
- 仅订单所属用户可操作

### 响应

```json
{
    "order_id": 1,
    "order_no": "RC20240101000001",
    "type": "balance",
    "type_label": "余额充值",
    "amount": "100.00",
    "received_amount": "100.00",
    "gateway": "wechat",
    "gateway_label": "微信支付",
    "status": "canceled",
    "status_label": "已取消",
    "payment_no": null,
    "remark": null,
    "paid_at": null,
    "completed_at": null,
    "expired_at": "2024-01-01T00:30:00Z",
    "created_at": "2024-01-01T00:00:00Z"
}
```

### 4. 充值订单列表

```
GET /recharge
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
            "order_id": 1,
            "order_no": "RC20240101000001",
            "type": "balance",
            "type_label": "余额充值",
            "amount": "100.00",
            "received_amount": "100.00",
            "gateway": "wechat",
            "gateway_label": "微信支付",
            "status": "completed",
            "status_label": "已完成",
            "payment_no": "WX20240101000001",
            "remark": null,
            "paid_at": "2024-01-01T00:01:00Z",
            "completed_at": "2024-01-01T00:01:01Z",
            "expired_at": "2024-01-01T00:30:00Z",
            "created_at": "2024-01-01T00:00:00Z"
        }
    ],
    "page": {
        "current": 1,
        "total_page": 1,
        "per_page": 15,
        "has_more": false,
        "total": 1
    }
}
```

---

## 提现

**前缀**: `/withdraw`

### 1. 获取可提现余额

```
GET /withdraw/balance
```

### 响应

```json
{
    "available_balance": "1000.00",
    "frozen_balance": "200.00"
}
```

| 字段 | 说明 |
|------|------|
| available_balance | 可提现余额（账户余额） |
| frozen_balance | 冻结金额（审核中的提现） |

### 2. 提现订单列表

```
GET /withdraw
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| per_page | int | 否 | 每页条数（默认15，最大50） |
| status | string | 否 | 按状态筛选：`pending`、`approved`、`processing`、`completed`、`rejected`、`cancelled` |

### 响应

```json
{
    "list": [
        {
            "order_id": 1,
            "order_no": "WD20240101000001",
            "amount": "200.00",
            "fee": "0.00",
            "actual_amount": "200.00",
            "gateway": "wechat",
            "gateway_label": "微信提现",
            "account_info": {
                "name": "张三",
                "account": "wx_123"
            },
            "status": "pending",
            "status_label": "待审核",
            "remark": null,
            "reject_reason": null,
            "reviewer_id": null,
            "reviewed_at": null,
            "paid_at": null,
            "payment_no": null,
            "created_at": "2024-01-01T00:00:00Z"
        }
    ],
    "page": {
        "current": 1,
        "total_page": 1,
        "per_page": 15,
        "has_more": false,
        "total": 1
    }
}
```

### 3. 创建提现订单

```
POST /withdraw
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| amount | decimal | 是 | 提现金额（≥0.01） |
| gateway | string | 是 | 提现方式：`wechat`（微信提现）、`alipay`（支付宝提现）、`bank`（银行卡提现） |
| account_info | object | 否 | 收款账户信息（`gateway=wechat` 时无需填写，openid 由后端解析） |
| account_info.name | string | 条件 | 收款人姓名（最大64字符，`gateway=alipay`/`bank` 必填；`wechat` 可选） |
| account_info.account | string | 条件 | 收款账号（最大64字符，`gateway=alipay`/`bank` 必填；`wechat` 不可填） |
| account_info.bank | string | 否 | 银行名称（`gateway=bank` 时建议填写，最大64字符） |
| account_info.branch | string | 否 | 支行名称（最大128字符） |
| payment_password | string | 是 | 支付密码（6位） |
| remark | string | 否 | 备注（最大255字符） |

### 响应

```json
{
    "order_id": 1,
    "order_no": "WD20240101000001",
    "amount": "200.00",
    "fee": "0.00",
    "actual_amount": "200.00",
    "gateway": "wechat",
    "gateway_label": "微信提现",
    "account_info": {
        "name": "张三",
        "account": "wx_123"
    },
    "status": "pending",
    "status_label": "待审核",
    "remark": null,
    "reject_reason": null,
    "reviewer_id": null,
    "reviewed_at": null,
    "paid_at": null,
    "payment_no": null,
    "created_at": "2024-01-01T00:00:00Z"
}
```

### 说明

- 创建成功后余额冻结，进入待审核状态
- 需已设置支付密码，余额需 ≥ 提现金额
- 实际到账金额 = 提现金额 - 手续费（手续费由后端计算，当前默认为 0）
- 实际到账金额不能小于 0.01
- 微信提现（`gateway=wechat`）：无需（也不允许）由前端填写收款 openid，收款 `account` 由后端从当前用户已绑定的微信账号中解析；未绑定微信时返回「请先绑定微信账号」

### 错误响应

| 场景 | HTTP 状态码 | 示例消息 |
|------|------------|---------|
| 未设置支付密码 | 400 | 请先设置支付密码 |
| 支付密码错误 | 400 | 支付密码错误 |
| 余额不足 | 400 | 余额不足 |
| 用户账户不存在 | 400 | 用户账户不存在 |
| 微信提现未绑定微信 | 400 | 请先绑定微信账号 |
| 参数验证失败 | 422 | 提现金额必须填写 |

### 4. 查询提现订单详情

```
GET /withdraw/{order}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| order | int | 提现订单 ID |

### 响应

与创建提现订单响应格式相同。仅订单所属用户可查看。

### 5. 取消提现订单

```
POST /withdraw/{order}/cancel
```

| 参数 | 类型 | 说明 |
|------|------|------|
| order | int | 提现订单 ID |

### 说明

- 仅待审核（`pending`）状态的订单可取消
- 取消后冻结金额自动退还到可用余额
- 仅订单所属用户可操作

### 响应

与创建提现订单响应格式相同，`status` 变为 `cancelled`。

---

### 提现状态流转

```
pending (待审核)
  ├── approved (审核通过) → processing (打款中) → completed (已完成)
  ├── rejected (已拒绝)
  └── cancelled (已取消)
```

### account_info 字段说明

根据 `gateway` 不同，`account_info` 结构有所差异：

**微信提现** (`gateway=wechat`)：

申请时无需（也不允许）传收款账号，`account`（openid）由后端从当前用户已绑定的微信账号自动解析并写入。`name` 可选（仅作展示用途）。

```json
{
    "name": "张三"
}
```

**支付宝提现** (`gateway=alipay`)：
```json
{
    "name": "张三",
    "account": "13800138000"
}
```

**银行卡提现** (`gateway=bank`)：
```json
{
    "name": "张三",
    "account": "6222021234567890123",
    "bank": "招商银行",
    "branch": "北京朝阳支行"
}
```

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
