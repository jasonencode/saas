# Other - 其他 API

**响应格式说明**：
- 错误响应返回 `{"code": 400, "message": "错误信息"}`

---

## 1. 应用版本检测

获取最新应用版本信息，支持根据当前版本判断是否需要更新。

```
GET /app_version?platform=ios&version=1.0.0&application_id=com.example.app
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| platform | string | 是 | 平台（ios / android） |
| version | string | 是 | 当前版本号 |
| application_id | string | 是 | 应用标识 |

### 有更新时的响应

```json
{
    "update": true,
    "application_id": "com.example.app",
    "description": "修复已知问题，提升用户体验",
    "version": "2.0.0",
    "force": false,
    "download": "https://...",
    "publish_at": "2024-01-01 00:00:00"
}
```

### 无更新时的响应

```json
{
    "update": false
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| update | bool | 是否需要更新 |
| version | string | 最新版本号 |
| description | string | 更新说明 |
| force | bool | 是否强制更新 |
| download | string | 下载地址 |
| publish_at | string | 发布时间 |

---

## 2. 服务器健康检查

Laravel 内置健康检查端点（`bootstrap/app.php` 中配置 `health: '/up'`）。

```
GET /up
```

### 响应

返回 200 状态码，用于健康检查和负载均衡探测。

---

## 3. 图片上传

**认证**: 需登录（`auth:sanctum`）  
**限流**: `throttle:uploads`

> 路由已注册于 `routes/apis/system.php`，前缀 `/system`。

### 单图上传

```
POST /system/upload/image
```

| 请求类型 | 说明 |
|---------|------|
| Content-Type | multipart/form-data |

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| file | file | 是 | 图片文件（支持 jpg, jpeg, png, gif, bmp, webp） |
| visibility | string | 否 | 文件可见性（public=公开, private=私有），默认 public |

### 多图上传

```
POST /system/upload/images
```

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| files | file[] | 是 | 图片文件数组（支持 jpg, jpeg, png, gif） |
| visibility | string | 否 | 文件可见性（public=公开, private=私有），默认 public |

### 响应

```json
{
    "url": "https://...",
    "path": "uploads/...",
    "name": "filename.jpg",
    "size": 102400
}
```

多图上传返回数组格式。

---

## 4. SSE 实时评论推送

Server-Sent Events 接口，用于实时推送评论数据。

> 路由注册于 `routes/web.php`，挂在**默认域名**（`custom.domains.default_domain`）下，非 API 域名，且无需登录。

```
GET /sse?last_id=0
```

### 查询参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| last_id | int | 是 | 客户端最后接收到的评论 ID（首次传 0） |

### 响应格式

Content-Type: `text/event-stream`

```
event: comment
data: {"id":1,"content":"评论内容"}

: ping

```

### 协议说明

- 使用 SSE (Server-Sent Events) 标准协议
- 每 0.8 秒轮询一次新评论
- 超过 120 秒无活动自动断开，客户端应自动重连
- 心跳包（ping）在无新数据时每 0.8 秒发送一次
- 禁用 Nginx 缓冲（`X-Accel-Buffering: no`）确保实时性
