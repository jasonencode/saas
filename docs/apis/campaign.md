# Campaign - 营销活动 API

**前缀**: `/campaign`

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
| limit | int | 否 | 每页条数（默认20，最大100） |

仅返回启用且在有效期内的优惠券。

### 2. 我的优惠券

需要认证（`auth:sanctum`）。

```
GET /campaign/coupons/my
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| is_used | bool | 否 | 是否已使用 |
| limit | int | 否 | 每页条数（默认20，最大100） |

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
    "code": 0,
    "message": "操作成功",
    "data": {
        "message": "优惠券领取成功",
        "coupon": { ... },
        "user_coupon": { ... }
    }
}
```

---

## 红包

### 5. 红包活动列表

```
GET /campaign/redpacks
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 否 | 活动名称（模糊搜索） |
| status | bool | 否 | 活动状态 |
| limit | int | 否 | 每页条数（默认20，最大100） |

### 6. 我的红包

需要认证（`auth:sanctum`）。

```
GET /campaign/redpacks/my
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| limit | int | 否 | 每页条数（默认20，最大100） |

### 7. 红包活动详情

```
GET /campaign/redpacks/{redpack}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| redpack | int | 红包活动 ID |

### 8. 红包码领取

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
    "code": 0,
    "message": "红包领取成功",
    "data": {
        "amount": "10.00",
        "claimed_at": "2024-01-01 12:00:00"
    }
}
```

---

## 抽奖

### 9. 抽奖活动列表

```
GET /campaign/lotteries
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 否 | 活动名称（模糊搜索） |
| status | bool | 否 | 活动状态 |
| limit | int | 否 | 每页条数（默认20，最大100） |

### 10. 抽奖活动详情

```
GET /campaign/lotteries/{lottery}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| lottery | int | 抽奖活动 ID |

返回活动详情及奖品列表。

### 11. 抽奖

需要认证（`auth:sanctum`）。

```
POST /campaign/lotteries/{lottery}/draw
```

### 12. 我的抽奖记录

需要认证（`auth:sanctum`）。

```
GET /campaign/lotteries/{lottery}/draws
```

### 13. 我的中奖记录

需要认证（`auth:sanctum`）。

```
GET /campaign/lotteries/{lottery}/prizes
```

### 14. 剩余抽奖次数

需要认证（`auth:sanctum`）。

```
GET /campaign/lotteries/{lottery}/available-draws
```

### 响应

```json
{
    "code": 0,
    "message": "操作成功",
    "data": {
        "available_draws": 3
    }
}
```
