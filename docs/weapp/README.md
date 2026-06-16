# SaaS.Foundation 小程序对接 SDK

> 微信小程序 JavaScript SDK，用于对接 SaaS.Foundation API

## 目录结构

```
weapp/
├── README.md               # 本文件 - 使用说明
├── app.js                  # 入口 - 组合所有模块导出
├── config.js               # 配置文件
├── utils/
│   ├── request.js          # 基础 HTTP 请求封装（自动 Token 管理）
│   ├── auth.js             # 租户签名认证工具
│   └── helpers.js          # 工具函数
└── services/
    ├── auth.js             # 认证模块
    ├── user.js             # 用户中心
    ├── mall.js             # 商城模块
    ├── campaign.js         # 营销活动
    ├── content.js          # 内容管理
    ├── chain.js            # 区块链
    ├── finance.js          # 财务管理
    └── other.js            # 其他（版本检测、上传、SSE）
```

## 快速开始

### 1. 复制文件

将 `weapp/` 整个目录复制到你的小程序项目 `utils/` 或 `sdk/` 目录下。

### 2. 配置 API 域名

在 `config.js` 中设置你的 API 域名：

```javascript
// config.js
module.exports = {
  API_DOMAIN: 'https://your-api-domain.com', // 修改为实际域名
  APP_KEY: 'your_app_key',                   // 租户 app_key（用于签名认证）
  APP_SECRET: 'your_app_secret',             // 租户 app_secret
}
```

### 3. 初始化

在 `app.js` 中引入 SDK：

```javascript
// app.js
const api = require('./utils/weapp/app');

App({
  onLaunch() {
    // 尝试从本地存储恢复登录态
    api.restoreToken();
  },
  globalData: {
    api
  }
})
```

### 4. 在页面中使用

```javascript
// pages/index/index.js
const api = require('../../utils/weapp/app');

Page({
  async onLoad() {
    // 无需登录的接口
    const products = await api.mall.getProducts({ limit: 10 });
    console.log('商品列表:', products);

    // 需要先登录
    const loginRes = await api.auth.login('username', 'password');
    console.log('登录结果:', loginRes);
  }
})
```

## 认证说明

### 用户认证（Sanctum Token）

- 登录成功后的 token 会自动保存在 `wx.getStorageSync('token')` 中
- 所有需要认证的接口请求会自动带上 `Authorization: Bearer <token>` 头
- 检测到 401 响应时自动清除失效 token

### 租户签名认证

用于租户获取访问令牌，使用 HMAC-SHA256 签名：

```
signature = HMAC-SHA256(app_secret, "app_key={app_key}&timestamp={timestamp}&nonce={nonce}")
```

SDK 内置了 `auth.getTenantToken()` 方法自动处理签名流程。

## 通用响应处理

所有接口返回统一格式，SDK 自动提取 `data` 字段：

```javascript
// 后端返回 { code: 0, message: "操作成功", data: { ... } }
// SDK 直接返回 data 部分

try {
  const data = await api.mall.getProducts({ limit: 10 });
  // 只有 data 部分
} catch (err) {
  // err.message 包含错误描述
  // err.code 包含业务状态码（非0）
}
```

## 注意事项

1. **小程序域名白名单**：需要在微信公众平台将 API 域名添加到 `request` 合法域名列表
2. **上传域名**：上传接口需要添加 `uploadFile` 合法域名
3. **并发限制**：wx.request 并发限制为 10 个，SDK 内部不做额外限制
4. **Token 有效期**：用户登录 token 过期时间由服务端控制，建议在应用启动时检查登录态
5. **租户 Token**：租户 token 有效期 2 小时，SDK 会监听 401 自动重试获取
