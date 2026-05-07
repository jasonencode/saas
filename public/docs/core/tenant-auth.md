# 租户 API 签名认证指南

## 📋 概述

为了提高安全性，租户获取 AccessToken 的方式已从简单的 `app_key + app_secret` 验证升级为基于 **HMAC-SHA256** 的签名验证机制。

---

## 🔐 签名算法说明

### 签名公式

```
signature = HMAC-SHA256(app_secret, sign_string)
```

### 待签名字符串

```
sign_string = "app_key={app_key}&timestamp={timestamp}&nonce={nonce}"
```

### 参数说明

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| app_key | string | 是 | 租户应用 Key |
| timestamp | integer | 是 | 当前时间戳（秒），允许误差±5 分钟 |
| nonce | string | 是 | 随机字符串，防止重放攻击 |
| signature | string | 是 | 计算得到的签名值 |

---

## 🚀 使用示例

### PHP SDK 示例

#### 方式一：使用 TenantSignatureHelper 辅助类

```php
use App\Helpers\TenantSignatureHelper;

$appKey = 'your_app_key';
$appSecret = 'your_app_secret';

// 自动生成所有参数
$params = TenantSignatureHelper::createSignedRequest($appKey, $appSecret);

// 发起请求
$response = Http::post('/api/auth/tenant/token', $params);

$result = $response->json();
$accessToken = $result['access_token'];
```

#### 方式二：手动生成签名

```php
$appKey = 'your_app_key';
$appSecret = 'your_app_secret';

// 生成时间戳和随机数
$timestamp = time();
$nonce = bin2hex(random_bytes(16));

// 构建待签名字符串
$signStr = sprintf(
    'app_key=%s&timestamp=%d&nonce=%s',
    $appKey,
    $timestamp,
    $nonce
);

// 生成签名
$signature = hash_hmac('sha256', $signStr, $appSecret);

// 发起请求
$response = Http::post('/api/auth/tenant/token', [
    'app_key' => $appKey,
    'timestamp' => $timestamp,
    'nonce' => $nonce,
    'signature' => $signature,
]);

$result = $response->json();
$accessToken = base64_decode($result['access_token']);
```

---

### cURL 命令行示例

```bash
#!/bin/bash

APP_KEY="your_app_key"
APP_SECRET="your_app_secret"

# 生成时间戳和随机数
TIMESTAMP=$(date +%s)
NONCE=$(openssl rand -hex 16)

# 生成签名
SIGNATURE=$(echo -n "app_key=${APP_KEY}&timestamp=${TIMESTAMP}&nonce=${NONCE}" | \
            openssl dgst -sha256 -hmac "${APP_SECRET}" | \
            awk '{print $NF}')

# 发起请求
curl -X POST http://your-domain.com/api/auth/tenant/token \
  -H "Content-Type: application/json" \
  -d "{
    \"app_key\": \"${APP_KEY}\",
    \"timestamp\": ${TIMESTAMP},
    \"nonce\": \"${NONCE}\",
    \"signature\": \"${SIGNATURE}\"
  }"
```

---

### JavaScript/Node.js 示例

```javascript
const crypto = require('crypto');

const APP_KEY = 'your_app_key';
const APP_SECRET = 'your_app_secret';

// 生成时间戳和随机数
const timestamp = Math.floor(Date.now() / 1000);
const nonce = crypto.randomBytes(16).toString('hex');

// 构建待签名字符串
const signStr = `app_key=${APP_KEY}&timestamp=${timestamp}&nonce=${nonce}`;

// 生成签名
const signature = crypto
  .createHmac('sha256', APP_SECRET)
  .update(signStr)
  .digest('hex');

// 发起请求
const axios = require('axios');

axios.post('http://your-domain.com/api/auth/tenant/token', {
  app_key: APP_KEY,
  timestamp: timestamp,
  nonce: nonce,
  signature: signature
})
.then(response => {
  const accessToken = Buffer.from(response.data.access_token, 'base64').toString();
  console.log('Access Token:', accessToken);
})
.catch(error => {
  console.error('Error:', error.response.data);
});
```

---

### Python 示例

```python
import hashlib
import hmac
import time
import secrets
import requests

APP_KEY = 'your_app_key'
APP_SECRET = 'your_app_secret'

# 生成时间戳和随机数
timestamp = int(time.time())
nonce = secrets.token_hex(16)

# 构建待签名字符串
sign_str = f'app_key={APP_KEY}&timestamp={timestamp}&nonce={nonce}'

# 生成签名
signature = hmac.new(
    APP_SECRET.encode('utf-8'),
    sign_str.encode('utf-8'),
    hashlib.sha256
).hexdigest()

# 发起请求
response = requests.post('http://your-domain.com/api/auth/tenant/token', json={
    'app_key': APP_KEY,
    'timestamp': timestamp,
    'nonce': nonce,
    'signature': signature
})

result = response.json()
access_token = base64.b64decode(result['access_token']).decode()
print('Access Token:', access_token)
```

---

## 📝 响应格式

### 成功响应

```json
{
  "access_token": "base64_encoded_token_string",
  "token_type": "Bearer",
  "expires_in": 7200
}
```

**注意：** `access_token` 是 Base64 编码的，需要解码后使用。

### 错误响应

#### 无效的 app_key

```json
{
  "code": 403,
  "message": "Invalid app_key"
}
```

#### 租户已禁用

```json
{
  "code": 403,
  "message": "Tenant has been disabled"
}
```

#### 租户已过期

```json
{
  "code": 403,
  "message": "Tenant has expired"
}
```

#### 时间戳无效

```json
{
  "code": 403,
  "message": "Timestamp is invalid or expired"
}
```

#### 签名无效

```json
{
  "code": 403,
  "message": "Invalid signature"
}
```

---

## 🔒 安全特性

### 1. 防重放攻击

- 使用时间戳验证，允许误差±5 分钟（300 秒）
- 每个请求使用不同的 nonce
- 超过时间窗口的请求会被拒绝

### 2. 签名保护

- 使用 HMAC-SHA256 算法
- app_secret 不在网络中传输
- 使用 `hash_equals` 防止时序攻击

### 3. Token 有效期

- AccessToken 有效期为 2 小时
- 过期后需要重新获取
- 支持 Base64 编码增加一层保护

---

## ⚠️ 注意事项

### 1. 时间同步

确保客户端服务器时间准确，与服务器时间误差不超过 5 分钟。建议使用 NTP 服务同步时间。

```bash
# Linux/Mac 检查时间
date

# Windows 检查时间
w32tm /query /status
```

### 2. Nonce 唯一性

每次请求必须使用不同的 nonce，建议长度至少 32 位字符。

### 3. 签名大小写

签名结果为小写十六进制字符串，比较时区分大小写。

### 4. Token 使用

获取到的 AccessToken 需要 Base64 解码后在 Authorization Header 中使用：

```
Authorization: Bearer {decoded_token}
```

---

## 🛠️ 测试工具

### Postman Pre-request Script

在 Postman 中可以这样自动生成签名：

```javascript
const appKey = pm.environment.get("APP_KEY");
const appSecret = pm.environment.get("APP_SECRET");
const timestamp = Math.floor(Date.now() / 1000);
const nonce = require('crypto').randomBytes(16).toString('hex');

const signStr = `app_key=${appKey}&timestamp=${timestamp}&nonce=${nonce}`;
const signature = require('crypto')
    .createHmac('sha256', appSecret)
    .update(signStr)
    .digest('hex');

pm.request.body.update({
    mode: 'raw',
    raw: JSON.stringify({
        app_key: appKey,
        timestamp: timestamp,
        nonce: nonce,
        signature: signature
    })
});
```

---

## 📚 相关代码

- 控制器：`app/Http/Controllers/Auth/LoginController.php`
- 请求验证：`app/Http/Requests/TenantTokenRequest.php`
- 签名助手：`app/Helpers/TenantSignatureHelper.php`

---

## 🔄 迁移指南

如果之前使用了旧的认证方式，需要：

1. 更新客户端代码以支持新的签名方式
2. 确保 app_key 和 app_secret 正确配置
3. 测试时间同步和签名生成
4. 逐步切换流量并监控错误率

---

## 💡 最佳实践

1. **定期轮换密钥** - 建议每 90 天更换一次 app_secret
2. **HTTPS 传输** - 始终使用 HTTPS 传输请求
3. **日志记录** - 记录所有认证请求用于审计
4. **错误处理** - 妥善处理认证失败，避免泄露敏感信息
5. **速率限制** - 对认证接口实施频率限制
