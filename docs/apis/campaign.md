# Campaign - 营销活动 API

**前缀**: `/campaign`

**响应格式说明**：
- 错误响应返回 `{"code": 400, "message": "错误信息"}`

---

## 优惠券

### 1. 优惠券列表

```
GET /campaign/coupons
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 否 | 优惠券类型（枚举值） |
| min_amount | decimal | 否 | 最低门槛金额 |
| max_amount | decimal | 否 | 最高门槛金额 |
| per_page | int | 否 | 每页条数（默认20，最大100） |

仅返回启用且在有效期内的优惠券。

#### 响应字段

| 字段 | 类型 | 说明 |
|------|------|------|
| coupon_id | int | 优惠券 ID |
| name | string | 优惠券名称 |
| description | string | 优惠券描述 |
| type | object | 优惠券类型（枚举） |
| discount_amount | decimal | 折扣值 |
| min_amount | decimal | 最低消费金额 |
| usage_limit | int | 发放数量限制（null 不限） |
| usage_limit_per_user | int | 每人限领数量（null 不限） |
| start_at | string | 开始时间 |
| end_at | string | 结束时间 |
| expired_type | object | 过期方式（枚举） |
| days | int | 有效天数（仅领取后生效类型） |
| status | bool | 启用状态 |
| state | string | 状态标签（已禁用/未开始/已过期/使用中） |
| user_state | string | 当前用户领取状态（见下表，未登录时字段不返回） |
| can_be_used | bool | 是否可被使用 |

**user_state 取值**：

| 值 | 说明 |
|------|------|
| `null` | 已登录但未领取 |
| `"claimed"` | 已领取，可使用 |
| `"used"` | 已使用 |
| `"expired"` | 已过期 |

### 2. 我的优惠券

需要认证（`auth:sanctum`）。

```
GET /campaign/coupons/my
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| is_used | bool | 否 | 是否已使用 |
| per_page | int | 否 | 每页条数（默认20，最大100） |

### 3. 优惠券详情

```
GET /campaign/coupons/{coupon}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| coupon | int | 优惠券 ID |

### 4. 领取优惠券

需要认证（`auth:sanctum`）。

```
POST /campaign/coupons/{coupon}/claim
```

| 参数 | 类型 | 说明 |
|------|------|------|
| coupon | int | 优惠券 ID |

### 响应

```json
{
    "message": "优惠券领取成功",
    "coupon": { ... },
    "user_coupon": { ... }
}
```

### 5. 优惠券数量统计

需要认证（`auth:sanctum`）。

```
GET /campaign/coupons/stats
```

#### 响应

```json
{
    "available": 3,
    "used": 5,
    "expired": 2
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| available | int | 可使用数量（未使用且未过期） |
| used | int | 已使用数量 |
| expired | int | 已过期数量（未使用但已过期） |

### 6. 结算可用券

需要认证（`auth:sanctum`）。

```
GET /campaign/coupons/available
```

结算页选券用：返回当前用户持有的、对所选商品可用的券实例与预估抵扣，**按租户分组**（跨店购物车每组独立判定）。

#### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| items | array | 是 | 待结算商品列表（至少 1 项） |
| items[].sku_id | int | 是 | SKU ID |
| items[].qty | int | 是 | 数量（≥1） |

#### 响应

```json
[
    {
        "tenant_id": 1,
        "coupons": [
            {
                "coupon_user_id": 88,
                "name": "满100减20",
                "type": { "value": "fixed", "label": "固定金额", "color": "primary" },
                "min_amount": "100.00",
                "expired_at": "2026-10-01 00:00:00",
                "applicable": true,
                "discount_preview": "20.00",
                "base_amount": "159.84",
                "inapplicable_reason": null
            },
            {
                "coupon_user_id": 92,
                "name": "满200减30",
                "type": { "value": "fixed", "label": "固定金额", "color": "primary" },
                "min_amount": "200.00",
                "expired_at": "2026-10-01 00:00:00",
                "applicable": false,
                "discount_preview": null,
                "base_amount": null,
                "inapplicable_reason": "订单金额未满足使用条件，最低需要 ￥200.00"
            }
        ]
    }
]
```

| 字段 | 类型 | 说明 |
|------|------|------|
| tenant_id | int | 租户 ID（该组券所属租户） |
| coupons[].coupon_user_id | int | 用户持券实例 ID，下单/预览传 `coupon_user_id` 用此值 |
| coupons[].applicable | bool | 对所选商品是否可用 |
| coupons[].discount_preview | string\|null | 预估抵扣金额（与下单同一计算路径，含最低实付 0.01 clamp） |
| coupons[].base_amount | string\|null | 该券的抵扣基数（适用商品小计，按身份折后单价计） |
| coupons[].inapplicable_reason | string\|null | 不可用原因，供前端置灰展示 |

> 注：**租户口径与「我的优惠券」「券数量统计」一致**——仅返回本次请求生效租户集合内的券：
> 携带 `X-Tenant-Id` 时收窄至该租户（且该租户须为用户已授权租户），未携带时取用户全部授权租户。
>
> 仅返回未使用、未过期且券定义有效的持券实例（已使用/已过期的不返回）；生效租户集合内、但对所选商品不可用的券仍会返回并附带原因。
> 入参商品仅跳过库存/可售校验，单价与下单同源，保证「此处可用的券下单必成功且金额一致」。

---

## 红包

### 7. 红包活动列表

```
GET /campaign/redpacks
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 否 | 活动名称（模糊搜索） |
| status | bool | 否 | 活动状态 |
| per_page | int | 否 | 每页条数（默认20，最大100） |

### 8. 我的红包

需要认证（`auth:sanctum`）。

```
GET /campaign/redpacks/my
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| per_page | int | 否 | 每页条数（默认20，最大100） |

### 9. 红包活动详情

```
GET /campaign/redpacks/{redpack}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| redpack | int | 红包活动 ID |

### 10. 红包码领取

需要认证（`auth:sanctum`）。

```
POST /campaign/redpacks/{code}/claim
```

| 参数 | 类型 | 说明 |
|------|------|------|
| code | string | 红包码（字母数字） |

### 响应

```json
{
    "amount": "10.00",
    "claimed_at": "2024-01-01 12:00:00"
}
```

---

## 抽奖

### 11. 抽奖活动列表

```
GET /campaign/lotteries
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 否 | 活动名称（模糊搜索） |
| status | bool | 否 | 活动状态 |
| per_page | int | 否 | 每页条数（默认20，最大100） |

### 12. 抽奖活动详情

```
GET /campaign/lotteries/{lottery}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| lottery | int | 抽奖活动 ID |

返回活动详情及奖品列表。

### 13. 抽奖

需要认证（`auth:sanctum`）。

```
POST /campaign/lotteries/{lottery}/draw
```

### 14. 我的抽奖记录

需要认证（`auth:sanctum`）。

```
GET /campaign/lotteries/{lottery}/draws
```

### 15. 我的中奖记录

需要认证（`auth:sanctum`）。

```
GET /campaign/lotteries/{lottery}/prizes
```

### 16. 剩余抽奖次数

需要认证（`auth:sanctum`）。

```
GET /campaign/lotteries/{lottery}/available-draws
```

### 响应

```json
{
    "available_draws": 3
}
```
