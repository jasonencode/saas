# Auth - 用户认证 API

**前缀**: `/auth`

---

## 1. 获取图形验证码

获取登录所需的图形验证码（图片 base64 和标识 Key）。

```
GET /auth/captcha
```

### 响应

```json
{
    "code": 0,
    "message": "操作成功",
    "data": {
        "key": "captcha_key_string",
        "img": "data:image/png;base64,..."
    }
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| key | string | 验证码标识 Key，提交登录时需要回传 |
| img | string | 验证码图片的 base64 数据 |

---

## 2. 账户密码登录

```
POST /auth/password
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| username | string | 是 | 用户名 |
| password | string | 是 | 密码 |
| captcha_key | string | 否 | 图形验证码 Key |
| captcha_code | string | 否 | 图形验证码 |

### 响应

```json
{
    "code": 0,
    "message": "登录成功",
    "data": {
        "user": { ... },
        "token": "sanctum_token_string",
        "token_type": "Bearer"
    }
}
```

---

## 3. 租户访问令牌

租户通过签名验证获取访问令牌。

```
POST /auth/tenant
```

### 签名算法

```
HMAC-SHA256(app_secret, "app_key={app_key}&timestamp={timestamp}&nonce={nonce}")
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| app_key | string | 是 | 应用 Key |
| timestamp | int | 是 | 当前时间戳（允许±5分钟误差，防止重放攻击） |
| nonce | string | 是 | 随机字符串 |
| signature | string | 是 | HMAC-SHA256 签名 |

### 响应

```json
{
    "code": 0,
    "message": "操作成功",
    "data": {
        "access_token": "base64_encoded_token",
        "token_type": "Bearer",
        "expires_in": 7200
    }
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| access_token | string | Base64 编码的访问令牌 |
| token_type | string | 令牌类型（Bearer） |
| expires_in | int | 过期时间（秒），2小时 |

### 错误码

| HTTP 状态码 | 说明 |
|-------------|------|
| 403 | app_key 无效 / 租户已禁用 / 租户已过期 / 签名无效 / 时间戳无效 |

---

## 4. 用户注册

```
POST /auth/register
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| username | string | 是 | 用户名 |
| password | string | 是 | 密码 |

### 响应

```json
{
    "code": 0,
    "message": "用户注册成功",
    "data": {
        "user": { ... },
        "token": "sanctum_token_string",
        "token_type": "Bearer"
    }
}
```

---

## 5. 发送短信验证码

```
POST /auth/sms
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| phone | string | 是 | 手机号码 |

### 响应

```
HTTP 204 No Content
```

---

## 6. 微信小程序手机号登录

```
POST /auth/mini/phone
```

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| code | string | 是 | 微信小程序获取的手机号授权 code |

### 响应

```json
{
    "code": 0,
    "message": "登录成功",
    "data": {
        "user": { ... },
        "token": "sanctum_token_string",
        "token_type": "Bearer"
    }
}
```

### 说明

- 通过 EasyWeChat 获取微信用户手机号
- 按手机号自动查找或创建用户
- 需要租户预先配置微信小程序 app_id 和 app_secret
