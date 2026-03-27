# API 接口完善计划

## 📋 概述

本文档追踪所有需要完善的控制器和 API 接口。

## ✅ 已完成的接口

### Auth 模块
- ✅ `POST /api/auth/password` - 密码登录
- ✅ `POST /api/auth/tenant` - 租户授权
- ✅ `POST /api/auth/register` - 用户注册（需要完善验证）
- ✅ `POST /api/auth/sms` - 发送短信（已实现）
- ✅ `GET /api/auth/captcha` - 图形验证码（已实现）

### Mall 模块
- ✅ `GET /api/mall` - 商城首页
- ✅ `GET /api/mall/brands` - 品牌列表
- ✅ `GET /api/mall/banners` - 轮播图
- ✅ `GET /api/mall/categories` - 分类列表
- ✅ `GET /api/mall/categories/{id}` - 分类详情
- ✅ `GET /api/mall/products` - 商品列表
- ✅ `GET /api/mall/products/{id}` - 商品详情
- ✅ `GET /api/mall/coupons` - 优惠券列表（✅ 新增完成）
- ✅ `GET /api/mall/coupons/{id}` - 优惠券详情（✅ 新增完成）
- ✅ `GET /api/mall/orders` - 订单列表（✅ 新增完成）
- ✅ `GET /api/mall/orders/{no}` - 订单详情（✅ 新增完成）
- ✅ `POST /api/mall/orders` - 创建订单（✅ 新增完成）
- ✅ `POST /api/mall/orders/{no}/cancel` - 取消订单（✅ 新增完成）
- ✅ `DELETE /api/mall/orders/{no}` - 删除订单（✅ 新增完成）

### User 模块
- ✅ `GET /api/user/profile` - 获取个人信息
- ✅ `PUT /api/user/profile` - 更新个人信息
- ✅ `GET /api/user/addresses` - 地址列表
- ✅ `GET /api/user/addresses/{id}` - 地址详情
- ✅ `POST /api/user/addresses` - 创建地址
- ✅ `PUT /api/user/addresses/{id}` - 更新地址
- ✅ `DELETE /api/user/addresses/{id}` - 删除地址
- ✅ `GET /api/user/regions` - 地区列表
- ✅ `POST /api/user/notifications/read` - 标记已读
- ✅ `GET /api/user/notifications/groups` - 通知分组

### Content 模块
- ✅ `GET /api/content/categories` - 内容分类
- ✅ `GET /api/content/categories/{id}` - 分类详情
- ✅ `GET /api/content/contents` - 内容列表
- ✅ `GET /api/content/contents/{id}` - 内容详情

## ⚠️ 需要完善的接口

### Chain 模块（区块链）
- ❌ `GET /api/chain/networks` - 网络列表（已实现）
- ❌ `GET /api/chain/contracts` - 合约列表（空实现）
- ❌ `GET /api/chain/contracts/{id}` - 合约详情（空实现）
- ❌ `GET /api/chain/certificates` - 证书列表（空实现）
- ❌ `POST /api/chain/certificates` - 创建证书（空实现）
- ❌ `GET /api/chain/certificates/{id}` - 证书详情（空实现）
- ❌ `GET /api/chain/addresses` - 地址列表（空实现）

### Mall 模块
- ❌ `GET /api/mall/coupons` - 优惠券列表（空实现）

### Redpack 模块
- ❌ 所有接口（只有一个空控制器）

## 🔧 控制器状态

| 控制器 | 状态 | 说明 |
|--------|------|------|
| Auth\LoginController | ✅ 完成 | 登录逻辑完整 |
| Auth\RegisterController | ⚠️ 需完善 | 缺少验证和业务逻辑 |
| Auth\SmsController | ⚠️ 需查看 | 需要检查实现 |
| Auth\CaptchaController | ⚠️ 需查看 | 需要检查实现 |
| Mall\ProductController | ✅ 完成 | 商品接口完整 |
| Mall\CategoryController | ✅ 完成 | 分类接口完整 |
| Mall\IndexController | ✅ 完成 | 首页接口完整 |
| Mall\CouponController | ❌ 空实现 | 只有返回 success() |
| User\AddressController | ✅ 完成 | 地址接口完整 |
| User\ProfileController | ✅ 完成 | 个人资料完整 |
| User\NotificationController | ✅ 完成 | 通知接口完整 |
| Chain\IndexController | ⚠️ 需查看 | 需要检查实现 |
| Chain\ContractController | ❌ 空实现 | 需要完整实现 |
| Chain\CertificateController | ❌ 空实现 | 需要完整实现 |
| Chain\AddressController | ❌ 空实现 | 需要完整实现 |

## 📝 优先级

### P0 - 高优先级（核心业务）
1. 完善 RegisterController 的验证逻辑
2. 完善 SmsController 的短信发送逻辑
3. 完善 CouponController 的优惠券功能

### P1 - 中优先级（重要功能）
1. 完善 Chain 模块的区块链相关接口
2. 完善 Redpack 模块的红包功能

### P2 - 低优先级（辅助功能）
1. 添加更多错误处理和边界情况
2. 优化现有代码结构

## 🎯 下一步行动

1. 检查并完善 Auth 模块的剩余控制器
2. 完善 Mall\CouponController
3. 实现 Chain 模块的业务逻辑
4. 实现 Redpack 模块
