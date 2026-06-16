# Content - 内容管理 API

**前缀**: `/contents`

---

## 内容

### 1. 内容列表

```
GET /contents
```

### 响应

```json
{
    "code": 0,
    "message": "操作成功",
    "data": [...],
    "meta": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 15,
        "total": 150
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
