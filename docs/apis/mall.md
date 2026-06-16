# Mall - 商城 API

**前缀**: `/mall`

---

## 商城首页

### 1. 商城首页

获取首页聚合数据（轮播图、品牌、推荐商品）。

```
GET /mall
```

### 响应

```json
{
    "code": 0,
    "message": "操作成功",
    "data": {
        "banners": [
            { "id": 1, "image_url": "...", "link_url": "...", "sort": 1 }
        ],
        "brands": [
            { "id": 1, "name": "品牌名", "logo": "...", "sort": 1 }
        ],
        "products": [
            {
                "id": 1,
                "name": "商品名",
                "image": "...",
                "price": "99.00",
                "sale": 500
            }
        ]
    }
}
```

### 2. 品牌列表

```
GET /mall/brands
```

### 3. 轮播图列表

```
GET /mall/banners
```

---

## 商品分类

### 4. 分类列表

```
GET /mall/categories
```

### 5. 分类详情

```
GET /mall/categories/{category}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| category | int | 分类 ID |

---

## 商品

### 6. 商品列表

```
GET /mall/products
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 否 | 商品名称（模糊搜索） |
| category_id | int | 否 | 分类 ID |
| brand_id | int | 否 | 品牌 ID |
| min_price | decimal | 否 | 最低价格 |
| max_price | decimal | 否 | 最高价格 |
| sort | string | 否 | 排序方式 |
| limit | int | 否 | 每页条数（默认15） |

### 7. 商品详情

```
GET /mall/products/{product}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| product | int | 商品 ID |

仅返回已上架的商品详情，包含 SKU 规格信息。

---

## 购物车

**前缀**: `/mall/cart`  
**认证**: 全部需要 `auth:sanctum`

### 8. 获取购物车列表

```
GET /mall/cart
```

### 响应

```json
{
    "code": 0,
    "message": "操作成功",
    "data": {
        "id": 1,
        "items": [
            {
                "id": 1,
                "product": { "id": 1, "name": "商品名", "image": "..." },
                "sku": { "id": 1, "name": "规格名", "price": "99.00" },
                "qty": 2,
                "sub_total": "198.00"
            }
        ],
        "total_amount": "198.00"
    }
}
```

### 9. 添加商品到购物车

```
POST /mall/cart/add
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| sku_id | int | 是 | 商品规格 ID |
| qty | int | 是 | 数量 |

### 10. 结算预览

```
POST /mall/cart/preview
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| item_ids | array | 是 | 购物车项目 ID 列表 |
| address_id | int | 否 | 收货地址 ID（传入后可计算运费） |

### 响应

```json
{
    "code": 0,
    "message": "操作成功",
    "data": {
        "items": [...],
        "addresses": [...],
        "address": null,
        "total_amount": "198.00",
        "freight": "0.00",
        "payable_amount": "198.00"
    }
}
```

### 11. 从购物车创建订单

```
POST /mall/cart/checkout
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| item_ids | array | 是 | 购物车项目 ID 列表 |
| address_id | int | 是 | 收货地址 ID |

### 说明

- 使用原子锁防止重复提交
- 下单成功后自动清除对应购物车商品

### 12. 更新购物车商品数量

```
PUT /mall/cart/items/{item}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| item | int | 购物车项目 ID |

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| qty | int | 是 | 新数量 |

### 13. 删除购物车商品

```
DELETE /mall/cart/items/{item}
```

### 14. 清空购物车

```
POST /mall/cart/clear
```

---

## 订单

**认证**: 全部需要 `auth:sanctum`

### 15. 订单列表

```
GET /mall/orders
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| status | string | 否 | 订单状态 |
| keyword | string | 否 | 搜索关键字（订单号/商品名） |
| limit | int | 否 | 每页条数（默认20） |

### 16. 订单详情

```
GET /mall/orders/{order}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| order | int | 订单 ID |

包含订单商品、规格、地址等完整信息。

### 17. 创建订单

```
POST /mall/orders
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| items | array | 是 | 商品列表 |
| items[].sku_id | int | 是 | 规格 ID |
| items[].qty | int | 是 | 数量 |
| items[].remark | string | 否 | 备注 |
| address_id | int | 是 | 收货地址 ID |

### 说明

- 使用原子锁防止重复提交

### 18. 取消订单

```
POST /mall/orders/{order}/cancel
```

### 19. 删除订单

```
DELETE /mall/orders/{order}
```
