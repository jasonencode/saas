# API 响应格式规范

## 1. 概述

统一 API 响应格式，确保前后端交互一致性。使用 `code` + `message` 结构，参考 JSON:API 规范设计。

## 2. 响应结构

### 2.1 成功响应

```json
{
    "code": 0,
    "message": "操作成功",
    "data": { ... }
}
```

**简化规则**：
- 单个资源直接返回，不包裹 `data` 层
- 数组数据直接返回，不包裹 `data` 层
- 只有消息没有数据时，只返回 `code` + `message`

```json
// 单个资源
{
    "content_id": 1,
    "title": "产品标题",
    "price": 100.00
}

// 列表数据
[
    { "content_id": 1, "title": "产品A" },
    { "content_id": 2, "title": "产品B" }
]

// 只有消息
{
    "code": 0,
    "message": "删除成功"
}
```

### 2.2 错误响应

```json
{
    "code": 1,
    "message": "操作失败",
    "errors": { ... }
}
```

| code | 说明 | HTTP Status |
|------|------|-------------|
| 0 | 成功 | 200, 201 |
| 1 | 一般错误 | 400 |
| 401 | 未认证 | 401 |
| 403 | 权限不足 | 403 |
| 404 | 资源不存在 | 404 |
| 429 | 验证错误 | 422 |
| 500 | 服务器错误 | 500 |

## 3. 分页响应

### 3.1 分页列表格式

```json
{
    "list": [
        { "id": 1, "name": "产品A" },
        { "id": 2, "name": "产品B" }
    ],
    "page": {
        "current": 1,
        "total_page": 7,
        "per_page": 15,
        "has_more": true,
        "total": 100
    }
}
```

**字段说明**：
- `list` - 数据列表
- `page` - 分页信息（可选）
  - `current` - 当前页码
  - `total_page` - 总页数
  - `per_page` - 每页数量
  - `has_more` - 是否有更多页
  - `total` - 总记录数

### 3.2 Cursor 分页格式（可选）

```json
{
    "list": [
        { "id": 1, "name": "产品A" },
        { "id": 2, "name": "产品B" }
    ],
    "page": {
        "per_page": 15,
        "next_cursor": "eyJpZCI6MTJ9",
        "has_more": true
    }
}
```

## 4. 验证错误响应

```json
{
    "code": 429,
    "message": "请求参数验证失败",
    "errors": {
        "email": ["邮箱格式不正确"],
        "password": ["密码长度不能少于6位"]
    }
}
```

## 5. 资源嵌套响应

### 5.1 包含关联数据

```json
{
    "id": 1,
    "title": "订单标题",
    "amount": 1000.00,
    "user": {
        "id": 1,
        "name": "张三"
    },
    "items": [
        { "id": 1, "product_id": 10, "quantity": 2 }
    ]
}
```

### 5.2 关系链接（JSON:API 风格）

```json
{
    "id": "1",
    "type": "orders",
    "attributes": {
        "title": "订单标题",
        "amount": 1000.00
    },
    "relationships": {
        "user": {
            "data": { "type": "users", "id": "1" }
        }
    },
    "included": [
        {
            "type": "users",
            "id": "1",
            "attributes": { "name": "张三" }
        }
    ]
}
```

## 6. 使用示例

### 6.1 ApiResponse 类

```php
use App\Http\Responses\ApiResponse;

// 成功响应
return ApiResponse::success($user);

// 创建成功
return ApiResponse::created($order);

// 成功但无数据
return ApiResponse::success(null, '删除成功');

// 失败响应
return ApiResponse::error('操作失败', 1);

// 验证错误
return ApiResponse::validationError([
    'email' => ['邮箱格式不正确'],
]);

// 未授权
return ApiResponse::unauthorized();

// 资源不存在
return ApiResponse::notFound('用户不存在');
```

### 6.2 资源类响应

```php
// 使用 API Resource
return new UserResource($user);

// 资源列表（使用 BaseCollection，自动带分页）
return new UserCollection($users);

// 不带分页的列表
return UserCollection::make($users)->withoutPagination();
```

**说明**：
- `BaseCollection` 自动处理分页，返回 `list` + `page` 结构
- 使用 `withoutPagination()` 方法可关闭分页

## 7. HTTP 状态码对照

| 场景 | HTTP Status | Response Code |
|------|-------------|---------------|
| 成功 | 200 | 0 |
| 创建成功 | 201 | 0 |
| 无内容 | 204 | 0 |
| 验证失败 | 422 | 429 |
| 未认证 | 401 | 401 |
| 权限不足 | 403 | 403 |
| 资源不存在 | 404 | 404 |
| 服务器错误 | 500 | 500 |

## 8. 命名规范

| 字段 | 类型 | 说明 |
|------|------|------|
| `code` | int | 状态码，0=成功，非0=失败 |
| `message` | string | 操作结果的描述信息 |
| `data` | mixed | 响应数据（可选，单个资源直接返回不包裹） |
| `list` | array | 列表数据（分页时使用） |
| `page` | object | 分页信息（可选） |
| `errors` | array | 错误详情（验证错误时使用） |

### 8.1 资源 ID 字段命名

资源的主键字段使用 `{模型名}_id` 格式，避免与前端或其他字段冲突：

| 模型 | ID 字段名 | 示例 |
|------|-----------|------|
| Content | `content_id` | `{ "content_id": 1, "title": "..." }` |
| User | `user_id` | `{ "user_id": 1, "name": "..." }` |
| Order | `order_id` | `{ "order_id": 1, "amount": 100 }` |

**在 Resource 中重命名字段**：
```php
public function toArray(Request $request): array
{
    return [
        'content_id' => $this->resource->id,  // 重命名为 content_id
        'title' => $this->resource->title,
        // ...
    ];
}
```

---

**版本**：1.0.0
**最后更新**：2026-04-24
