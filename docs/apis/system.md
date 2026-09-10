# System 模块 API

## 应用版本

### 获取版本更新信息

检查应用是否有新版本可更新。

**请求**

```
GET /api/app_version
```

**参数**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `platform` | string | 是 | 平台类型: `android`, `ios`, `apad`, `ipad`, `wmp` |
| `application_id` | string | 是 | 应用包名 (如 `com.example.app`) |
| `version` | string | 是 | 当前版本号 (如 `1.0.0`) |

**响应 — 无需更新**

```json
{
    "code": 200,
    "message": "版本信息获取成功",
    "data": {
        "update": false
    }
}
```

**响应 — 有新版本**

```json
{
    "code": 200,
    "message": "版本信息获取成功",
    "data": {
        "update": true,
        "application_id": "com.example.app",
        "description": "修复若干问题，优化用户体验",
        "version": "1.2.0",
        "force": false,
        "download": "https://releases.example.com/app-v1.2.0.apk",
        "publish_at": "2024-01-15 10:00:00"
    }
}
```

---

## 文件上传

### 上传单张图片

上传单张图片，返回文件信息。

**请求**

```
POST /api/system/upload/image
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

**参数**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `file` | file | 是 | 图片文件，支持 jpg, jpeg, png, gif, bmp, webp |
| `visibility` | string | 否 | 可见性：`public`（默认，公开）、`private`（私有，`url` 返回临时签名链接） |

> 证件照等敏感资料上传时传 `visibility=private`：文件以私有权限存储，`url` 为短期签名链接（过期失效），防止链接被外部长期访问。本地开发磁盘不支持签名链接时回退为公开 URL。

**响应**

```json
{
    "code": 200,
    "message": "success",
    "data": {
        "uuid": "d4f5e6a7b8c9d0e1f2a3b4c5d6e7f8a9",
        "name": "photo.jpg",
        "size": 102400,
        "url": "https://cdn.example.com/storage/2024/01/15/d4f5e6a7b8c9d0e1f2a3b4c5d6e7f8a9.jpg",
        "path": "2024/01/15/d4f5e6a7b8c9d0e1f2a3b4c5d6e7f8a9.jpg"
    }
}
```

---

### 上传多张图片

批量上传多张图片，返回文件信息列表。

**请求**

```
POST /api/system/upload/images
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

**参数**

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `files[]` | file[] | 是 | 图片文件数组，支持 jpg, jpeg, png, gif |
| `visibility` | string | 否 | 可见性：`public`（默认）、`private`（私有，`url` 返回临时签名链接） |

**响应**

```json
{
    "code": 200,
    "message": "success",
    "data": [
        {
            "uuid": "d4f5e6a7b8c9d0e1f2a3b4c5d6e7f8a9",
            "name": "photo1.jpg",
            "size": 102400,
            "url": "https://cdn.example.com/storage/2024/01/15/d4f5e6a7b8c9d0e1f2a3b4c5d6e7f8a9.jpg",
            "path": "2024/01/15/d4f5e6a7b8c9d0e1f2a3b4c5d6e7f8a9.jpg"
        },
        {
            "uuid": "a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6",
            "name": "photo2.png",
            "size": 204800,
            "url": "https://cdn.example.com/storage/2024/01/15/a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6.png",
            "path": "2024/01/15/a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6.png"
        }
    ]
}
```
