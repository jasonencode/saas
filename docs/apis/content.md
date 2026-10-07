# Content - 内容管理 API

**前缀**: `/contents`

**响应格式说明**：
- 错误响应返回 `{"code": 400, "message": "错误信息"}`

---

## 内容

### 1. 内容列表

```
GET /contents
```

### 响应

```json
{
    "list": [...],
    "page": {
        "total": 150,
        "per_page": 15,
        "current_page": 1,
        "last_page": 10
    }
}
```

### 2. 内容详情

```
GET /contents/{content}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| content | int | 内容 ID |

查看时会自动增加浏览量（`views` +1）。

---

## 内容评论

### 3. 获取内容评论列表

```
GET /contents/{content}/comments
```

| 参数 | 类型 | 说明 |
|------|------|------|
| content | int | 内容 ID |

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| per_page | int | 否 | 每页条数（默认15，最大50） |

### 4. 发表评论

需要认证（`auth:sanctum`）。

```
POST /contents/{content}/comments
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| content | string | 是* | 评论内容（与 pictures 二选一） |
| star | int | 否 | 评分（1-5） |
| pictures | array | 否 | 图片列表 |
| pictures.* | string | 否 | 图片 URL |

---

## 内容分类

### 5. 分类列表

```
GET /contents/categories
```

### 6. 分类详情

```
GET /contents/categories/{category}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| category | int | 分类 ID |

---

## 内容标签

### 7. 标签列表

```
GET /contents/tags
```

按使用量排序返回内容标签列表。

---

## 单页内容

### 8. 单页内容列表

```
GET /contents/single-pages
```

### 9. 单页内容详情

```
GET /contents/single-pages/{slug}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| slug | string | 单页别名 |

---

## 意见反馈

> 以下接口均需登录（`auth:sanctum`）。

### 10. 提交反馈

```
POST /contents/suggests
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| type | string | 是 | 反馈类型（feature=功能建议, bug=问题反馈, account=账号问题, other=其他） |
| content | string | 是 | 反馈内容（5-500 字符） |
| contact | string | 否 | 联系方式（最长 100 字符） |

### 响应

HTTP 201，返回创建的反馈：

```json
{
    "suggest_id": 1,
    "type": {
        "value": "bug",
        "label": "问题反馈"
    },
    "contact": "wx: jason",
    "status": {
        "value": "pending",
        "label": "待处理"
    },
    "last_message_at": null,
    "created_at": "2024-01-01T00:00:00Z"
}
```

### 11. 我的反馈列表

```
GET /contents/suggests
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| status | string | 否 | 状态筛选（pending=待处理, resolved=已解决, closed=已关闭） |
| page | int | 否 | 页码（默认 1） |
| per_page | int | 否 | 每页条数（默认 20，最大 50） |

### 响应

分页列表，`list` 中元素结构同「提交反馈」响应（不含 `contact` 之外的额外字段）。

```json
{
    "list": [
        {
            "suggest_id": 1,
            "type": { "value": "bug", "label": "问题反馈" },
            "contact": "wx: jason",
            "status": { "value": "pending", "label": "待处理" },
            "last_message_at": "2024-01-01T00:10:00Z",
            "created_at": "2024-01-01T00:00:00Z"
        }
    ],
    "page": { "current": 1, "total_page": 1, "per_page": 20, "has_more": false, "total": 1 }
}
```

### 12. 反馈详情（对话列表）

仅反馈所属用户可访问，无权访问返回 HTTP 403。

```
GET /contents/suggests/{suggest}
```

| 参数 | 类型 | 说明 |
|------|------|------|
| suggest | int | 反馈 ID |

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| page | int | 否 | 页码（默认 1） |
| per_page | int | 否 | 每页条数（默认 20） |

### 响应

```json
{
    "suggest": {
        "suggest_id": 1,
        "type": { "value": "bug", "label": "问题反馈" },
        "status": { "value": "pending", "label": "待处理" }
    },
    "messages": {
        "list": [
            {
                "message_id": 1,
                "content": "订单无法支付",
                "is_from_user": true,
                "sender": {
                    "user_id": 1,
                    "nickname": "Jason",
                    "avatar": "https://..."
                },
                "created_at": "2024-01-01T00:00:00Z"
            },
            {
                "message_id": 2,
                "content": "您好，已收到反馈",
                "is_from_user": false,
                "sender": {
                    "admin_id": 1,
                    "name": "客服"
                },
                "created_at": "2024-01-01T00:05:00Z"
            }
        ],
        "page": { "current": 1, "total_page": 1, "per_page": 20, "has_more": false, "total": 2 }
    }
}
```

> `sender` 为用户时返回 `user_id`/`nickname`/`avatar`，为管理员时返回 `admin_id`/`name`。

### 13. 追加反馈消息

反馈已关闭（`closed`）时返回业务错误。

```
POST /contents/suggests/{suggest}/messages
```

| 参数 | 类型 | 说明 |
|------|------|------|
| suggest | int | 反馈 ID |

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| content | string | 是 | 消息内容（1-500 字符） |

### 响应

HTTP 201，返回创建的消息，结构同「反馈详情」中 `messages.list` 的元素。
