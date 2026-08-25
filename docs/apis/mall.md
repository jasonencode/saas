# Mall - 商城 API

**前缀**: `/mall`  
**中间件**: `store.opened`（店铺已开通校验）

**响应格式说明**：
- 错误响应返回 `{"code": 400, "message": "错误信息"}`
- 无内容响应（如删除、更新成功）返回 `{"code": 0, "message": "操作成功"}`

---

## 商城首页

### 1. 商城首页

获取首页聚合数据（轮播图、分类、品牌、推荐商品）。

```
GET /mall
```

### 响应

```json
{
    "banners": [
        {
            "banner_id": 1,
            "title": "轮播图标题",
            "cover": "https://...",
            "jump": "https://..."
        }
    ],
    "categories": [
        {
            "category_id": 1,
            "level": 1,
            "name": "分类名",
            "description": "分类描述",
            "cover": "https://...",
            "children": []
        }
    ],
    "brands": [
        {
            "brand_id": 1,
            "name": "品牌名"
        }
    ],
    "products": [
        {
            "goods_id": 1,
            "name": "商品名",
            "cover": "https://...",
            "price": "99.00",
            "origin_price": "199.00",
            "views": 100,
            "sales": 500,
            "store": { "tenant_id": 1, "store_name": "...", "logo": "...", "phone": "...", "contactor": "...", "address": "..." },
            "brand": { "brand_id": 1, "name": "品牌名" }
        }
    ]
}
```

### 2. 品牌列表

```
GET /mall/brands
```

### 响应

```json
[
    { "brand_id": 1, "name": "品牌名" }
]
```

### 3. 轮播图列表

```
GET /mall/banners
```

### 响应

```json
[
    {
        "banner_id": 1,
        "title": "轮播图标题",
        "cover": "https://...",
        "jump": "https://..."
    }
]
```

---

## 商品分类

### 4. 分类列表

```
GET /mall/categories
```

返回树形结构的商品分类列表。

### 响应

```json
[
    {
        "category_id": 1,
        "level": 1,
        "name": "分类名",
        "description": "分类描述",
        "cover": "https://...",
        "children": [
            {
                "category_id": 2,
                "level": 2,
                "name": "子分类名",
                "description": "子分类描述",
                "cover": "https://...",
                "children": []
            }
        ]
    }
]
```

### 5. 分类详情

```
GET /mall/categories/{category}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| category | int | 分类 ID |

仅返回已启用的分类详情，包含下级分类。

---

## 标签

### 6. 商品标签列表

```
GET /mall/tags
```

按使用量排序返回商品标签列表。

### 响应

```json
[
    {
        "tag_id": 1,
        "name": "标签名",
        "products_count": 10
    }
]
```

---

## 物流公司

### 7. 物流公司列表

```
GET /mall/expresses
```

返回可用的物流公司列表（退货物流选择）。

### 响应

```json
[
    { "id": 1, "name": "顺丰速运" }
]
```

---

## 商品

### 8. 商品列表

```
GET /mall/products
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | 否 | 商品名称（模糊搜索） |
| category_id | int | 否 | 分类 ID |
| brand_id | int | 否 | 品牌 ID |
| tag_id | int | 否 | 标签 ID |
| min_price | decimal | 否 | 最低价格（按 SKU 价格筛选） |
| max_price | decimal | 否 | 最高价格（按 SKU 价格筛选） |
| sort | string | 否 | 排序方式（默认按最新排序） |
| per_page | int | 否 | 每页条数（受 `custom.pagination.max_per_page` 限制） |

### 响应

```json
{
    "data": [
        {
            "goods_id": 1,
            "name": "商品名",
            "cover": "https://...",
            "price": "99.00",
            "origin_price": "199.00",
            "views": 100,
            "sales": 500,
            "store": { "tenant_id": 1, "store_name": "...", "logo": "...", "phone": "...", "contactor": "...", "address": "..." },
            "brand": { "brand_id": 1, "name": "品牌名" }
        }
    ],
    "page": { "total": 100, "per_page": 20, "current_page": 1, "last_page": 5 }
}
```

### 9. 商品详情

```
GET /mall/products/{product}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| product | int | 商品 ID |

仅返回已上架的商品详情，包含 SKU 规格信息。

### 响应

```json
{
    "goods_id": 1,
    "name": "商品名",
    "description": "商品描述",
    "cover": "https://...",
    "pictures": ["https://..."],
    "materials": ["https://..."],
    "price": "99.00",
    "origin_price": "199.00",
    "total_stock": 500,
    "views": 100,
    "total_sale": 500,
    "store": { "tenant_id": 1, "store_name": "...", "logo": "...", "phone": "...", "contactor": "...", "address": "..." },
    "brand": { "brand_id": 1, "name": "品牌名" },
    "tags": [
        { "tag_id": 1, "name": "标签名" }
    ],
    "can_cart": true,
    "fulfillment_types": ["mail"],
    "skus": [
        {
            "sku_id": 1,
            "name": "规格名",
            "code": "SKU编码",
            "cover": "https://...",
            "price": "99.00",
            "origin_price": "199.00",
            "stock": 100,
            "sale": 50
        }
    ]
}
```

---

## 购物车

**前缀**: `/mall/cart`  
**认证**: 全部需要 `auth:sanctum`

### 10. 获取购物车列表

```
GET /mall/cart
```

### 响应

```json
{
    "cart_id": 1,
    "items": [
        {
            "item_id": 1,
            "product": {
                "product_id": 1,
                "name": "商品名",
                "cover": "https://...",
                "fulfillment_types": ["mail", "pickup"]
            },
            "sku": {
                "sku_id": 1,
                "name": "规格名"
            },
            "qty": 2,
            "price": "99.00",
            "sub_total": "198.00",
            "is_available": true
        }
    ],
    "total_qty": 2,
    "total_amount": "198.00",
    "is_expired": false
}
```

### 11. 添加商品到购物车

```
POST /mall/cart/add
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| sku_id | int | 是 | 商品规格 ID |
| qty | int | 是 | 数量（1-9999） |

### 响应

返回完整的购物车详情（同「获取购物车列表」）。

### 12. 结算预览

```
POST /mall/cart/preview
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| fulfillment_type | string | 是 | 履约方式：`mail`（快递邮寄）、`pickup`（门店自提）、`virtual`（虚拟商品） |
| item_ids | array | 是 | 购物车项目 ID 列表（至少 1 项） |
| address_id | int | 否 | 收货地址 ID（`mail` 履约方式时传入可计算运费） |

### 说明

- 所有选中商品必须支持同一种履约方式，否则报错
- 仅 `mail` 履约方式按运费模板计算运费，`pickup`/`virtual` 免运费

### 响应

```json
{
    "items": [
        {
            "item_id": 1,
            "product": { "product_id": 1, "name": "商品名", "cover": "https://...", "fulfillment_types": ["mail"] },
            "sku": { "sku_id": 1, "name": "规格名" },
            "qty": 2,
            "price": "99.00",
            "sub_total": "198.00",
            "is_available": true
        }
    ],
    "addresses": [
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
    ],
    "address": null,
    "total_amount": "198.00",
    "freight": "0.00",
    "payable_amount": "198.00"
}
```

### 13. 从购物车创建订单

```
POST /mall/cart/checkout
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| fulfillment_type | string | 是 | 履约方式：`mail`（快递邮寄）、`pickup`（门店自提）、`virtual`（虚拟商品） |
| item_ids | array | 是 | 购物车项目 ID 列表（至少 1 项） |
| address_id | int | 条件 | 收货地址 ID（`mail` 履约方式时必填） |
| pickup_point_id | int | 条件 | 自提点 ID（`pickup` 履约方式时必填） |

### 说明

- 使用原子锁防止重复提交
- 下单成功后自动清除对应购物车商品
- 返回新创建的订单 ID 列表

### 响应

```json
[1, 2]
```

### 14. 更新购物车商品数量

```
PUT /mall/cart/items/{item}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| item | int | 购物车项目 ID |

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| qty | int | 是 | 新数量（1-9999） |

### 响应

返回更新后的购物车详情（同「获取购物车列表」）。

### 15. 删除购物车商品

```
DELETE /mall/cart/items/{item}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| item | int | 购物车项目 ID |

### 响应

返回更新后的购物车详情（同「获取购物车列表」）。

### 16. 清空购物车

```
POST /mall/cart/clear
```

### 响应

返回空购物车详情（同「获取购物车列表」，items 为空数组）。

---

## 订单

**前缀**: `/mall/orders`  
**认证**: 全部需要 `auth:sanctum`

### 17. 订单列表

```
GET /mall/orders
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| status | string | 否 | 订单状态 |
| keyword | string | 否 | 搜索关键字（订单号/商品名） |
| per_page | int | 否 | 每页条数（受 `custom.pagination.max_per_page` 限制） |

### 响应

```json
{
    "data": [
        {
            "order_id": 1,
            "no": "202501010001",
            "status": {
                "value": "pending",
                "label": "待付款"
            },
            "total_amount": "198.00",
            "amount": "198.00",
            "freight": "0.00",
            "items": [
                {
                    "item_id": 1,
                    "orderable": {
                        "id": 1,
                        "type": "App\\Models\\Mall\\Sku",
                        "name": "商品名 - 规格名",
                        "cover": "https://..."
                    },
                    "qty": 2,
                    "price": "99.00",
                    "sub_total": "198.00",
                    "remark": ""
                }
            ],
            "expired_at": "2025-01-01 12:00:00",
            "paid_at": null,
            "signed_at": null,
            "created_at": "2025-01-01 10:00:00"
        }
    ],
    "page": { "total": 100, "per_page": 20, "current_page": 1, "last_page": 5 }
}
```

### 18. 订单详情

```
GET /mall/orders/{order}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| order | int | 订单 ID |

包含订单商品、规格、地址等完整信息。

### 响应

```json
{
    "order_id": 1,
    "no": "202501010001",
    "status": {
        "value": "pending",
        "label": "待付款"
    },
    "fulfillment_type": {
        "value": "mail",
        "label": "快递邮寄"
    },
    "total_amount": "198.00",
    "amount": "198.00",
    "freight": "0.00",
    "items_quantity": 2,
    "items": [
        {
            "item_id": 1,
            "orderable": {
                "id": 1,
                "type": "App\\Models\\Mall\\Sku",
                "name": "商品名 - 规格名",
                "cover": "https://..."
            },
            "qty": 2,
            "price": "99.00",
            "sub_total": "198.00",
            "remark": ""
        }
    ],
    "address": {
        "name": "张三",
        "mobile": "13800138000",
        "address": "详细地址",
        "region": {
            "province_id": 1,
            "city_id": 2,
            "district_id": 3
        }
    },
    "user": {
        "user_id": 1,
        "username": "用户名"
    },
    "expired_at": "2025-01-01 12:00:00",
    "paid_at": null,
    "signed_at": null,
    "verified_at": null,
    "pickup_code": null,
    "pickup_point": null,
    "created_at": "2025-01-01 10:00:00"
}
```

### 19. 订单状态统计

```
GET /mall/orders/status-count
```

获取当前用户常用订单状态的数量统计，包括待付款、待发货、待收货。

### 响应

```json
{
    "pending": 3,
    "wait_shipping": 2,
    "wait_receive": 5
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| pending | int | 待付款订单数量（status=pending） |
| wait_shipping | int | 待发货订单数量（status=paid,preparing） |
| wait_receive | int | 待收货订单数量（status=partially,delivered） |

### 20. 创建订单

```
POST /mall/orders
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| fulfillment_type | string | 是 | 履约方式：`mail`（快递邮寄）、`pickup`（门店自提）、`virtual`（虚拟商品） |
| items | array | 是 | 商品列表 |
| items[].product_sku_id | int | 是 | 规格 ID |
| items[].qty | int | 是 | 数量 |
| items[].remark | string | 否 | 备注（最长 255 字符） |
| address_id | int | 条件 | 收货地址 ID（`mail` 履约方式时必填） |
| pickup_point_id | int | 条件 | 自提点 ID（`pickup` 履约方式时必填） |
| remark | string | 否 | 订单备注（最长 255 字符） |

### 说明

- 使用原子锁防止重复提交

### 响应

```json
{
    "code": 0,
    "message": "创建成功"
}
```

### 21. 取消订单

```
POST /mall/orders/{order}/cancel
```

| 参数 | 类型 | 说明 |
|------|------|------|
| order | int | 订单 ID |

### 响应

```json
{
    "code": 0,
    "message": "订单取消成功"
}
```

### 22. 确认收货

```
POST /mall/orders/{order}/sign
```

| 参数 | 类型 | 说明 |
|------|------|------|
| order | int | 订单 ID |

### 响应

```json
{
    "code": 0,
    "message": "订单确认收货成功"
}
```

### 23. 删除订单

```
DELETE /mall/orders/{order}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| order | int | 订单 ID |

### 响应

```json
{
    "code": 0,
    "message": "订单删除成功"
}
```

---

## 退款/售后

**前缀**: `/mall/refunds`  
**认证**: 全部需要 `auth:sanctum`

### 24. 申请退款

```
POST /mall/orders/{order}/refund
```

| 参数 | 类型 | 说明 |
|------|------|------|
| order | int | 订单 ID |

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 是 | 退款类型：`only_refund`（仅退款）、`return_refund`（退货退款） |
| reason | string | 是 | 退款原因（见下方枚举） |
| reason_detail | string | 否 | 退款原因详情（最长 500 字符） |
| items | array | 是 | 退款商品列表（至少 1 项） |
| items[].order_item_id | int | 是 | 订单商品 ID |
| items[].qty | int | 是 | 退款数量（大于 0） |

### 退款原因枚举

| 值 | 说明 |
|------|------|
| not_want | 不想要了 |
| wrong_order | 拍错/多拍 |
| not_received | 未收到货 |
| late_delivery | 未按时发货 |
| quality | 质量问题 |
| damaged | 商品破损 |
| not_as_described | 描述不符 |
| size | 尺寸不合适 |
| wrong_item | 发错货 |
| missing_item | 少发/漏发 |
| counterfeit | 假货 |
| other | 其他 |

> **注意**: `only_refund`（仅退款）支持所有原因；`return_refund`（退货退款）不支持 `wrong_order`、`not_received`、`late_delivery`、`counterfeit`。

### 响应

```json
{
    "refund_id": 1,
    "no": "R202501010001",
    "order": {
        "order_id": 1,
        "no": "202501010001"
    },
    "status": {
        "value": "pending",
        "label": "待审核"
    },
    "type": {
        "value": "only_refund",
        "label": "仅退款"
    },
    "reason": {
        "value": "quality",
        "label": "质量问题"
    },
    "reason_detail": "商品有质量问题",
    "goods_amount": "99.00",
    "freight_amount": "0.00",
    "total": "99.00",
    "items": [
        {
            "item_id": 1,
            "order_item_id": 1,
            "orderable": {
                "orderable_id": 1,
                "name": "商品名"
            },
            "qty": 1,
            "price": "99.00",
            "remark": ""
        }
    ],
    "express": null,
    "logs": [],
    "approved_at": null,
    "refund_at": null,
    "created_at": "2025-01-01 10:00:00"
}
```

### 25. 退款列表

```
GET /mall/refunds
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| status | string | 否 | 退款状态 |
| per_page | int | 否 | 每页条数（受 `custom.pagination.max_per_page` 限制） |

### 响应

```json
{
    "data": [
        {
            "refund_id": 1,
            "no": "R202501010001",
            "order": {
                "order_id": 1,
                "no": "202501010001"
            },
            "status": {
                "value": "pending",
                "label": "待审核"
            },
            "type": {
                "value": "only_refund",
                "label": "仅退款"
            },
            "reason": {
                "value": "quality",
                "label": "质量问题"
            },
            "reason_detail": "商品有质量问题",
            "goods_amount": "99.00",
            "freight_amount": "0.00",
            "total": "99.00",
            "items": [],
            "express": null,
            "logs": [],
            "approved_at": null,
            "refund_at": null,
            "created_at": "2025-01-01 10:00:00"
        }
    ],
    "page": { "total": 10, "per_page": 20, "current_page": 1, "last_page": 1 }
}
```

### 26. 退款详情

```
GET /mall/refunds/{refund}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| refund | int | 退款 ID |

包含商品明细、物流、日志等完整信息。

### 响应

```json
{
    "refund_id": 1,
    "no": "R202501010001",
    "order": {
        "order_id": 1,
        "no": "202501010001"
    },
    "status": {
        "value": "pending",
        "label": "待审核"
    },
    "type": {
        "value": "return_refund",
        "label": "退货退款"
    },
    "reason": {
        "value": "quality",
        "label": "质量问题"
    },
    "reason_detail": "商品有质量问题",
    "goods_amount": "99.00",
    "freight_amount": "0.00",
    "total": "99.00",
    "items": [
        {
            "item_id": 1,
            "order_item_id": 1,
            "orderable": {
                "orderable_id": 1,
                "name": "商品名"
            },
            "qty": 1,
            "price": "99.00",
            "remark": ""
        }
    ],
    "express": {
        "express_id": 1,
        "express_name": "顺丰速运",
        "express_no": "SF1234567890",
        "status": {
            "value": "shipped",
            "label": "已发货"
        },
        "shipped_at": "2025-01-02 10:00:00",
        "received_at": null
    },
    "logs": [
        {
            "action": {
                "value": "create",
                "label": "申请退款"
            },
            "remark": "用户申请退款",
            "context": null,
            "created_at": "2025-01-01 10:00:00"
        }
    ],
    "approved_at": null,
    "refund_at": null,
    "created_at": "2025-01-01 10:00:00"
}
```

### 27. 取消退款

```
POST /mall/refunds/{refund}/cancel
```

| 参数 | 类型 | 说明 |
|------|------|------|
| refund | int | 退款 ID |

### 响应

```json
{
    "code": 0,
    "message": "退款已取消"
}
```

### 28. 提交退货物流

```
POST /mall/refunds/{refund}/ship
```

| 参数 | 类型 | 说明 |
|------|------|------|
| refund | int | 退款 ID |

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| express_id | int | 是 | 物流公司 ID |
| express_no | string | 是 | 物流单号（最长 32 字符） |

### 响应

```json
{
    "code": 0,
    "message": "退货物流提交成功"
}
```

---

## 订单物流

**前缀**: `/mall/orders/{order}`  
**认证**: 需要 `auth:sanctum`

### 29. 获取订单物流信息

```
GET /mall/orders/{order}/shipping
```

| 参数 | 类型 | 说明 |
|------|------|------|
| order | int | 订单 ID |

返回订单的所有发货记录，包含快递公司、物流单号、发货商品等信息。

### 响应

```json
[
    {
        "shipping_id": 1,
        "express": {
            "express_id": 1,
            "name": "顺丰速运"
        },
        "express_no": "SF1234567890",
        "items": [
            {
                "item_id": 1,
                "product": {
                    "product_id": 1,
                    "name": "商品名",
                    "cover": "https://..."
                },
                "sku": {
                    "sku_id": 1,
                    "name": "规格名"
                },
                "qty": 2
            }
        ],
        "address": {
            "name": "张三",
            "mobile": "13800138000",
            "address": "详细地址",
            "region": {
                "province_id": 1,
                "city_id": 2,
                "district_id": 3
            }
        },
        "delivery_at": "2025-01-02 10:00:00",
        "sign_at": null,
        "created_at": "2025-01-02 09:00:00"
    }
]
```

---

## 订单日志

**前缀**: `/mall/orders/{order}`  
**认证**: 需要 `auth:sanctum`

### 30. 获取订单操作日志

```
GET /mall/orders/{order}/logs
```

| 参数 | 类型 | 说明 |
|------|------|------|
| order | int | 订单 ID |

按时间倒序返回订单的所有操作日志，包含状态变更、发货、退款等记录。

### 响应

```json
[
    {
        "log_id": 1,
        "action": {
            "value": "created",
            "label": "订单创建"
        },
        "operator": {
            "id": 1,
            "type": "App\\Models\\User\\User",
            "name": "用户名"
        },
        "context": null,
        "created_at": "2025-01-01 10:00:00"
    },
    {
        "log_id": 2,
        "action": {
            "value": "paid",
            "label": "订单支付"
        },
        "operator": {
            "id": 1,
            "type": "App\\Models\\User\\User",
            "name": "用户名"
        },
        "context": {
            "payment_method": "wechat"
        },
        "created_at": "2025-01-01 10:05:00"
    }
]
```

---

## 商品评价

**前缀**: `/mall/products/{product}`  
**认证**: 需要 `auth:sanctum`

### 31. 评价商品

```
POST /mall/products/{product}/comment
```

| 参数 | 类型 | 说明 |
|------|------|------|
| product | int | 商品 ID |

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| star | int | 是 | 评分（1-5） |
| content | string | 是 | 评价内容（5-500 字符） |
| pictures | array | 否 | 评价图片（最多 9 张） |

### 说明

- 同一商品仅能评价一次

### 响应

```json
{
    "comment_id": 1,
    "star": 5,
    "content": "商品质量很好，物流很快",
    "created_at": "2025-01-03 10:00:00"
}
```

---

## 自提点

### 32. 自提点列表

```
GET /mall/pickup-points
```

返回当前租户已启用的自提点列表，用于门店自提履约方式选择。

### 响应

```json
[
    {
        "pickup_point_id": 1,
        "name": "深圳南山店",
        "contact": "李店长",
        "phone": "13800138000",
        "address": "科技园路 1 号",
        "full_address": "广东省 深圳市 南山区 科技园路 1 号",
        "region": {
            "province_id": 1,
            "city_id": 2,
            "district_id": 3
        },
        "sort": 0
    }
]
```

---

## 退货地址

### 33. 退货地址列表

```
GET /mall/return-address
```

返回当前租户已启用的退货地址列表，用于退款退货时选择退货地址。

### 响应

```json
[
    {
        "return_address_id": 1,
        "name": "退货接收人",
        "contact": "王经理",
        "phone": "13800138000",
        "address": "仓库路 1 号",
        "full_address": "广东省 深圳市 南山区 仓库路 1 号",
        "region": {
            "province_id": 1,
            "city_id": 2,
            "district_id": 3
        },
        "is_default": true,
        "sort": 0
    }
]
```

---

## 商品收藏

**认证**: 全部需要 `auth:sanctum`

### 34. 获取收藏列表

```
GET /mall/favorites
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| per_page | int | 否 | 每页条数（受 `custom.pagination.max_per_page` 限制） |

### 响应

```json
{
    "data": [
        {
            "goods_id": 1,
            "name": "商品名",
            "cover": "https://...",
            "price": "99.00",
            "origin_price": "199.00",
            "views": 100,
            "sales": 500,
            "store": { "tenant_id": 1, "store_name": "...", "logo": "...", "phone": "...", "contactor": "...", "address": "..." },
            "brand": { "brand_id": 1, "name": "品牌名" }
        }
    ],
    "page": { "total": 10, "per_page": 20, "current_page": 1, "last_page": 1 }
}
```

### 35. 收藏/取消收藏商品

```
POST /mall/products/{product}/favorite
```

| 参数 | 类型 | 说明 |
|------|------|------|
| product | int | 商品 ID |

切换收藏状态：已收藏则取消，未收藏则添加。

### 响应

```json
{
    "is_favorited": true
}
```

### 36. 检查商品是否已收藏

```
GET /mall/products/{product}/favorite
```

| 参数 | 类型 | 说明 |
|------|------|------|
| product | int | 商品 ID |

### 响应

```json
{
    "is_favorited": true
}
```
