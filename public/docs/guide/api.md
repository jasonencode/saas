# API 总览

## 概述

- **基础域名**: 由 `config('custom.domains.api_domain')`（环境变量 `API_DOMAIN`）配置，所有模块路由均绑定到该域名
- **数据格式**: 全部请求和响应均为 JSON
- **认证方式**: Sanctum Token，通过 `Authorization: Bearer <token>` 请求头传递
- **租户上下文**: 需要租户隔离的接口通过 `X-Tenant-Id` 请求头携带租户 ID（`TenantResolver` 解析）
- **频率限制**: 全部 API 默认经过 `throttle:api` 限流（60 次/分钟，见 [频率限制](rate-limits)）

## 通用响应格式

成功响应（`ApiResponse::success`，单个资源或数组直接返回，不包裹 `data` 层）：

```json
{
    "code": 0,
    "message": "操作成功"
}
```

分页响应：

```json
{
    "list": [ ... ],
    "page": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 20,
        "total": 200
    }
}
```

错误响应（`ApiResponse::error`）：

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
| 403 | 无权限 / 租户认证失败 / 租户已禁用或过期 |
| 404 | 资源不存在 |
| 422 | 参数验证失败 |
| 429 | 请求频率超限 |

异常统一由 `App\Http\Handlers\ApiExceptionHandler` 处理（`bootstrap/app.php` 中注册）。

### 通用查询参数

| 参数 | 类型 | 说明 |
|------|------|------|
| limit | int | 每页条数（默认20，最大100） |
| page | int | 页码（默认1） |

## 模块列表

| 模块 | 前缀 | 认证 | 描述 |
|------|------|------|------|
| [Auth](#auth-模块) | `/auth` | 公开 | 登录、注册、验证码 |
| [User](#user-模块) | `/user` | 全部需要 | 用户中心、地址、发票、通知、身份、隶属关系 |
| [Mall](#mall-模块) | `/mall` | 部分需要 | 商城首页、商品、购物车、订单、退款 |
| [Campaign](#campaign-模块) | `/campaign` | 部分需要 | 优惠券、红包、抽奖 |
| [Content](#content-模块) | `/contents` | 部分需要 | 内容、评论、分类、标签、单页 |
| [Chain](#chain-模块) | `/chain` | 全部需要 | 区块链、合约、证书 |
| [Finance](#finance-模块) | `/payments`、`/vouchers` | 全部需要 | 支付、凭证 |
| [Other](#other-模块) | `/`、`/app_version` | 公开 | 健康检查、版本检测 |

## Auth 模块

路由文件：`routes/apis/auth.php`（公开接口，`login` / `sms` 限流器保护）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/auth/captcha` | 获取图形验证码（含 key，用于后续校验） |
| POST | `/auth/sms` | 发送短信验证码 |
| POST | `/auth/password` | 账号密码登录 |
| POST | `/auth/tenant` | 租户登录（HMAC-SHA256 签名认证，见 [租户 API 签名认证](../core/tenant-auth)） |
| POST | `/auth/mini/phone` | 微信小程序手机号快捷登录 |
| POST | `/auth/register` | 用户注册（手机号 + 短信验证码） |

## User 模块

路由文件：`routes/apis/user.php`（全模块 `auth:sanctum`）。

详细接口文档见 [用户中心 API](../api/user-center-api)。

| 分组 | 路径 | 说明 |
|------|------|------|
| 资料 | `GET/PUT /user/profile` | 获取 / 修改个人资料 |
| 账户 | `GET /user/account`、`GET /user/account/logs` | 账户信息 / 变动日志 |
| 安全 | `GET /user/safe/records`、`PUT /user/safe/password`、`POST /user/safe/logout` | 登录记录 / 改密 / 登出 |
| 地址 | `/user/addresses`（CRUD + `default` + `regions`） | 收货地址管理、省市区 |
| 通知 | `/user/notifications`（列表 / 分组 / 已读 / 删除 / 计数） | 通知管理 |
| 隶属关系 | `GET /user/relations`、`GET /user/relations/overview`、`POST /user/relations/bind/{parentId}` | 推荐关系 |
| 发票抬头 | `/user/invoice-titles`（CRUD + `default`） | 发票抬头管理 |
| 发票 | `/user/invoices`（统计 / 可开票订单 / 申请 / 列表 / 详情） | 发票与申请 |
| 身份 | `GET /user/identities`、`GET /user/identities/available/{tenantId}`、`GET /user/identities/{identity}/check` | 身份管理 |

## Mall 模块

路由文件：`routes/apis/mall.php`。

| 分组 | 路径 | 认证 | 说明 |
|------|------|------|------|
| 首页 | `GET /mall` | 公开 | 商城首页聚合数据 |
| 店铺 | `GET /mall/stores`、`GET /mall/stores/{tenantId}` | 公开 | 店铺列表 / 店铺信息 |
| 品牌 | `GET /mall/brands` | 公开 | 品牌列表 |
| 轮播 | `GET /mall/banners` | 公开 | 首页轮播图 |
| 分类 | `GET /mall/categories`、`GET /mall/categories/{category}` | 公开 | 分类列表（树形）/ 详情 |
| 标签 | `GET /mall/tags` | 公开 | 商品标签（按使用量排序） |
| 商品 | `GET /mall/products`、`GET /mall/products/{product}` | 公开 | 商品列表 / 详情 |
| 商品评价 | `POST /mall/products/{product}/comment` | 需登录 | 评价商品 |
| 购物车 | `GET /mall/cart`、`POST /mall/cart/add`、`POST /mall/cart/preview`、`POST /mall/cart/checkout`、`PUT /mall/cart/items/{item}`、`DELETE /mall/cart/items/{item}`、`POST /mall/cart/clear` | 需登录 | 购物车（见 [购物车模块](cart)） |
| 收藏 | `GET /mall/favorites`、`POST /mall/products/{product}/favorite`、`GET /mall/products/{product}/favorite` | 需登录 | 商品收藏 |
| 退货地址 | `GET /mall/return-address` | 公开 | 退货地址列表 |
| 自提点 | `GET /mall/pickup-points` | 公开 | 自提点列表 |
| 物流公司 | `GET /mall/expresses` | 公开 | 物流公司列表 |
| 订单 | `GET /mall/orders`、`GET /mall/orders/status-count`、`POST /mall/orders/preview`、`GET /mall/orders/{order}`、`GET /mall/orders/{order}/shipping`、`GET /mall/orders/{order}/logs`、`POST /mall/orders`、`POST /mall/orders/{order}/cancel`、`POST /mall/orders/{order}/sign`、`DELETE /mall/orders/{order}` | 需登录 | 订单全流程（订单号 `no` 作为路由键） |
| 退款 | `POST /mall/orders/{order}/refund`、`GET /mall/refunds`、`GET /mall/refunds/{refund}`、`POST /mall/refunds/{refund}/cancel`、`POST /mall/refunds/{refund}/ship` | 需登录 | 退款 / 售后 |

## Campaign 模块

路由文件：`routes/apis/campaign.php`。

| 分组 | 路径 | 认证 | 说明 |
|------|------|------|------|
| 优惠券 | `GET /campaign/coupons` | 公开 | 优惠券列表 |
| | `GET /campaign/coupons/my` | 需登录 | 我的优惠券 |
| | `GET /campaign/coupons/stats` | 需登录 | 优惠券统计 |
| | `GET /campaign/coupons/{coupon}` | 公开 | 优惠券详情 |
| | `POST /campaign/coupons/{coupon}/claim` | 需登录 | 领取优惠券 |
| 红包 | `GET /campaign/redpacks` | 公开 | 红包列表 |
| | `GET /campaign/redpacks/my` | 需登录 | 我的红包 |
| | `GET /campaign/redpacks/{redpack}` | 公开 | 红包详情 |
| | `POST /campaign/redpacks/{code}/claim` | 需登录 | 按码领取红包 |
| 抽奖 | `GET /campaign/lotteries` | 公开 | 抽奖活动列表 |
| | `GET /campaign/lotteries/{lottery}` | 公开 | 活动详情 |
| | `POST /campaign/lotteries/{lottery}/draw` | 需登录 | 抽奖 |
| | `GET /campaign/lotteries/{lottery}/draws` | 需登录 | 我的抽奖记录 |
| | `GET /campaign/lotteries/{lottery}/prizes` | 需登录 | 我的中奖记录 |
| | `GET /campaign/lotteries/{lottery}/available-draws` | 需登录 | 可抽奖次数 |

## Content 模块

路由文件：`routes/apis/content.php`（前缀 `/contents`）。

| 路径 | 认证 | 说明 |
|------|------|------|
| `GET /contents` | 公开 | 内容列表 |
| `GET /contents/{content}` | 公开 | 内容详情 |
| `GET /contents/{content}/comments` | 公开 | 评论列表 |
| `POST /contents/{content}/comments` | 需登录 | 发表评论 |
| `GET /contents/categories` | 公开 | 分类列表 |
| `GET /contents/categories/{category}` | 公开 | 分类详情 |
| `GET /contents/tags` | 公开 | 标签列表 |
| `GET /contents/single-pages` | 公开 | 单页列表 |
| `GET /contents/single-pages/{slug}` | 公开 | 单页详情 |

## Chain 模块

路由文件：`routes/apis/chain.php`（全部 `auth:sanctum`）。

| 路径 | 说明 |
|------|------|
| `GET /chain/networks` | 网络列表 |
| `GET /chain/contracts` | 合约列表 |
| `GET /chain/contracts/{contract}` | 合约详情 |
| `GET /chain/certificates` | 证书列表 |
| `POST /chain/certificates` | 创建证书（存证） |
| `GET /chain/certificates/{certificate}` | 证书详情 |
| `GET /chain/addresses` | 地址列表 |

## Finance 模块

路由文件：`routes/apis/finance.php`（`payments` / `vouchers` 前缀，全部 `auth:sanctum`）。

| 路径 | 说明 |
|------|------|
| `POST /payments/{payment}/notify` | 支付回调通知（支付网关） |
| `POST /payments` | 创建支付单 |
| `GET /payments/{payment}` | 支付单详情 |
| `POST /payments/{payment}/pay` | 发起支付 |
| `POST /payments/{payment}/refund` | 支付退款 |
| `GET /vouchers` | 凭证列表 |

## Other 模块

路由文件：`routes/api.php`。

| 路径 | 说明 |
|------|------|
| `GET /` | 服务器健康检查（`Server is working`） |
| `GET /app_version` | 获取当前应用版本（小程序升级检测） |
