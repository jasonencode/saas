# SaaS.Foundation API 接口文档

> 基础平台 SaaS 化 API 接口文档

## 概述

- **基础域名**: 由 `config('custom.domains.api_domain')` 配置
- **数据格式**: 全部请求和响应均为 JSON
- **认证方式**: 使用 **Sanctum Token** 进行认证，通过 `Authorization: Bearer <token>` 请求头传递
- **租户签名认证**: 租户调用需使用 HMAC-SHA256 签名

### 通用响应格式

```json
{
    "code": 0,
    "message": "操作成功",
    "data": { ... },
    "meta": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 20,
        "total": 200
    }
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| code | int | 业务状态码（0=成功, 非0=异常） |
| message | string | 提示信息 |
| data | mixed | 响应数据 |
| meta | object | 分页信息（仅分页接口返回） |

### 通用错误响应

```json
{
    "code": 422,
    "message": "验证失败",
    "errors": {
        "field": ["错误信息"]
    }
}
```

### HTTP 状态码说明

| 状态码 | 说明 |
|--------|------|
| 200 | 请求成功 |
| 201 | 创建成功 |
| 204 | 操作成功（无返回内容） |
| 400 | 业务错误 |
| 403 | 无权限 / 租户认证失败 |
| 404 | 资源不存在 |
| 422 | 参数验证失败 |
| 429 | 请求频率超限 |

### 通用查询参数

| 参数 | 类型 | 说明 |
|------|------|------|
| limit | int | 每页条数（默认20，最大100） |
| page | int | 页码（默认1） |

### 模块列表

| 模块 | 前缀 | 认证 | 描述 |
|------|------|------|------|
| [Auth](auth.md) | `/auth` | 部分需要 | 用户认证、登录注册 |
| [User](user.md) | `/user` | 全部需要 | 用户中心、地址、发票、通知 |
| [Mall](mall.md) | `/mall` | 部分需要 | 商城首页、商品、购物车、订单 |
| [Campaign](campaign.md) | `/campaign` | 部分需要 | 优惠券、红包、抽奖活动 |
| [Content](content.md) | `/contents` | 部分需要 | 内容管理、评论 |
| [Chain](chain.md) | `/chain` | 不需要 | 区块链、智能合约、证书 |
| [Finance](finance.md) | `/payments`, `/vouchers` | 全部需要 | 支付、退款、结算凭据 |
| [Other](other.md) | `/app_version`, `/upload`, `/sse` | 视具体 | 版本检测、文件上传、SSE推送 |
