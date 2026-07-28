# Saas.Foundation

基于 **Laravel 13** 和 **Filament 5** 构建的企业级 SaaS 基础框架。

[![Laravel Version](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Filament Version](https://img.shields.io/badge/Filament-5-FCB900?style=flat-square)](https://filamentphp.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php)](https://php.net)
[![Horizon Version](https://img.shields.io/badge/Horizon-5-47A248?style=flat-square)](https://laravel.com/docs/horizon)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

## 简介

Saas.Foundation 提供了一套完整的 SaaS 应用基础架构，采用**运营端 + 租户端双面板**架构，覆盖商城、财务、内容管理、区块链、营销活动等核心业务域。

### 核心特性

- 🏗️ **多租户架构** - 运营端 + 租户端双 Filament 面板，完善的角色和权限隔离
- 🎨 **沉浸式深色主题** - 基于 Spotify 设计系统的深色主题，内容优先的视觉体验
- 🔐 **RBAC 权限控制** - 基于 Policy + Role 的细粒度授权策略体系
- 📦 **模块化设计** - 按业务域独立拆分（Mall、Finance、Content、BlockChain 等）
- ⚡ **Horizon 队列监控** - 内置 Horizon 仪表盘，实时监控队列任务
- 🔔 **多渠道通知** - 钉钉、极光推送、微信小程序/公众号、短信、站内信
- 🔗 **区块链集成** - 多链适配（Chain33、FISCO-BCOS）、合约管理、证书签发
- 💰 **完整财务体系** - 账户资金、支付、发票、退款、凭证、结算计划
- 🎯 **营销引擎** - 优惠券、红包码、抽奖活动
- 🌐 **API + 小程序** - RESTful API 文档 + 微信小程序 SDK

## 模块概览

| 模块 | 说明 | 运营端 | 租户端 |
|------|------|:------:|:------:|
| 🏢 **租户管理** (User) | 租户生命周期、管理员、角色权限、实名认证、身份通道 | ✅ | ✅ |
| 🛍️ **商城** (Mall) | 商品、订单、购物车、品牌、分类、属性、配送、退款、供应商 | ✅ | ✅ |
| 💰 **财务** (Finance) | 资金账户、支付网关、发票（申请/抬头/发票/凭证）、退款、结算计划 | ✅ | ✅ |
| 📄 **内容管理** (Content) | 内容发布、分类、评论、通知、敏感词 | ✅ | ✅ |
| 🎯 **营销活动** (Campaign) | 优惠券、红包码、抽奖活动 | ✅ | ✅ |
| 🔗 **区块链** (BlockChain) | 多链网络管理、合约部署/仓库、地址管理、证书签发 | ✅ | ✅ |
| 🔌 **基础服务** (Foundation) | 支付宝、阿里云、微信支付、微信小程序/公众号、社交登录 | ✅ | ✅ |
| ⚙️ **系统设置** (Setting) | 系统配置、管理员、角色权限、队列监控、API 日志、黑白名单、导入导出 | ✅ | ❌ |

## 技术栈

| 组件 | 版本 | 说明 |
|------|:----:|------|
| Laravel | v13 | 核心框架 |
| Filament | v5 | 管理面板 |
| Livewire | v4 | 动态组件 |
| Horizon | v5 | 队列监控 |
| Sanctum | v4 | API 认证 |
| PHP | 8.4 | 运行环境 |

## 项目结构

```
Saas.Foundation
├── app/
│   ├── Channels/              # 通知渠道（钉钉、极光、微信、短信、站内信）
│   ├── Console/               # Artisan 命令（AdminUser、Mall、Test、User）
│   ├── Contracts/             # 接口定义（通知、支付、结算、资产、策略）
│   ├── Enums/                 # 枚举类型（按模块组织，含状态机 Trait）
│   ├── Events/                # 领域事件（Mall/订单、Finance、User）
│   ├── Export/                # 导出模块（基础导出、User 导出）
│   ├── Filament/              # Filament 面板
│   │   ├── Actions/           #   通用 Action（按模块组织）
│   │   ├── Backend/           #   运营端面板（Clusters, Resources, Pages, Widgets）
│   │   ├── Tenant/            #   租户端面板（Clusters, Resources, Pages, Widgets）
│   │   ├── Exports/           #   面板导出器
│   │   ├── Forms/             #   自定义表单组件
│   │   ├── Infolists/         #   自定义信息列表组件
│   │   └── Tables/            #   自定义表格组件/过滤器
│   ├── Http/                  # HTTP 层
│   │   ├── Controllers/       #   按模块组织（Auth、Mall、Finance、User 等）
│   │   ├── Handlers/          #   异常处理器
│   │   ├── Middleware/        #   中间件（API 认证、日志、IP 黑名单）
│   │   ├── Requests/          #   表单验证请求
│   │   ├── Resources/         #   API 资源（按模块组织）
│   │   └── Responses/         #   API 统一响应
│   ├── Jobs/                  # 队列任务
│   ├── Listeners/             # 事件监听器（Mall/订单流转、Finance）
│   ├── Livewire/              # Livewire 组件（Homepage、TopbarDropdown）
│   ├── Models/                # Eloquent 模型（按模块组织）
│   ├── Notifications/         # 通知类（Mall、Finance、Demo）
│   ├── Policies/              # 授权策略（按模块组织）
│   ├── Providers/             # 服务提供者
│   ├── Rules/                 # 自定义验证规则（Mall、Campaign、身份证、手机号）
│   ├── Services/              # 业务服务层（按模块组织）
│   ├── Support/               # 支撑层
│   │   ├── BlockChain/        #   多链适配（Chain33/FISCO-BCOS）、ABI/Rlp/Rpc
│   │   ├── Certificate/       #   证书签发（CSR、KeyPair）
│   │   ├── Filesystem/        #   文件系统适配（本地/OSS）
│   │   ├── RSA/               #   RSA 加密工具
│   │   ├── Sigma/             #   Sigma 协议
│   │   ├── SmsGateways/       #   短信网关（调试网关）
│   │   └── TenantResolver/    #   租户解析器
│   └── Tasks/                 # 定时任务（DirectReward、SecondReward）
├── bootstrap/                 # 框架引导文件、helpers.php
├── config/                    # 配置文件（按模块组织）
├── database/                  # 数据库
│   ├── migrations/            #   迁移文件（用户、商城、财务、区块链、营销等）
│   ├── factories/             #   模型工厂
│   └── seeders/               #   数据填充
├── docs/                      # 文档
│   ├── apis/                  #   API 接口文档
│   └── weapp/                 #   微信小程序 SDK + 服务调用封装
├── lang/                      # 多语言（zh_CN、zh_TW、en）
├── resources/                 # 视图、CSS、JS 资源
├── routes/                    # 路由
│   ├── api.php                #   API 路由
│   ├── apis/                  #   模块化 API 路由（auth、mall、finance 等）
│   ├── web.php                #   Web 路由
│   └── console.php            #   Artisan 路由
├── stubs/                     # 自定义 stub 模板
├── storage/                   # 存储目录
├── tests/                     # 测试（Feature、Unit）
├── composer.json
├── pint.json                  # Laravel Pint 配置
└── vite.config.js             # Vite 构建配置
```

## 快速开始

### 环境要求

- PHP >= 8.4
- Composer
- Node.js & NPM / pnpm
- MySQL / MariaDB / PostgreSQL / SQLite
- Redis（队列驱动，推荐）
- 扩展：bcmath、gmp、openssl、pdo、zip

### 安装

```bash
# 克隆项目
git clone <repo-url>
cd Saas.Foundation

# 安装 PHP 依赖
composer install --no-dev -vvv --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix

# 安装前端依赖
pnpm install

# 配置环境变量
cp .env.example .env
php artisan key:generate

# 编辑 .env 配置数据库和 Redis 连接

# 初始化数据库
php artisan migrate
php artisan db:seed

# 构建前端资源
pnpm build

# 启动开发服务器
php artisan serve
```

### Docker（推荐使用 Laravel Sail）

```bash
# 使用 Sail 启动（需安装 Docker）
php artisan sail:install
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
```

## 开发指南

### 运行测试

```bash
# 运行所有测试
php artisan test

# 运行特定测试文件
php artisan test --filter=TestName

# 运行特定测试类
php artisan test tests/Feature/Mall/OrderTest.php
```

### 代码格式化

项目使用 Laravel Pint 进行代码格式化：

```bash
vendor/bin/pint --dirty --format agent
```

### 常用命令

```bash
# 清除所有缓存
php artisan optimize:clear

# 应用优化
php artisan optimize
php artisan filament:optimize

# 查看路由列表
php artisan route:list

# 启动 Horizon 队列监控
php artisan horizon

# 查看 Horizon 队列状态
php artisan horizon:status

# 生成模型、控制器等
php artisan make:model Product -mfsc
php artisan make:controller Api/V1/ProductController --api

# 启动开发服务器（带 Reverb 通知）
php artisan reverb:start
```

## 通知系统

支持多渠道消息推送，统一接口：

| 渠道 | 通道类 | 说明 |
|------|--------|------|
| 🔔 钉钉 | `DingTalkChannel` | 钉钉机器人/工作通知 |
| 📱 极光推送 | `JPushChannel` | APP 推送 |
| 💬 微信小程序 | `WechatMiniChannel` | 小程序订阅消息 |
| 📢 微信公众号 | `WechatOfficialChannel` | 公众号模板消息 |
| 📨 短信 | `SmsChannel` | 短信验证码/通知 |
| 🏢 站内信 | `TenantChannel` | 平台内通知 |

## 事件驱动

系统基于 Laravel 事件系统构建了领域事件驱动架构：

**商城事件流**：`OrderCreated → OrderPaid → OrderPreparing → OrderDelivered → OrderSigned → OrderCompleted`

| 事件 | 用途 |
|------|------|
| `OrderCreated` / `OrderPaid` / `OrderDelivered` / `OrderSigned` / `OrderCompleted` / `OrderCanceled` | 订单全生命周期 |
| `RefundInitialized` / `RefundCompleted` / `RefundFailed` | 退款流程 |
| `InvoiceApplicationSubmitted` / `InvoiceIssued` | 发票流程 |
| `UserCreatedEvent` / `IdentityChanged` / `IdentityPurchased` / `UserRealnameApproved` | 用户生命周期 |

## API 文档

项目提供了完整的 RESTful API，支持微信小程序和第三方客户端接入：

- **认证接口** - 登录、注册、密码重置、Token 管理
- **商城接口** - 商品列表、下单、购物车、订单管理
- **财务接口** - 余额查询、支付下单、发票申请
- **内容接口** - 文章、分类、评论
- **区块链接口** - 合约查询、证书验证
- **用户接口** - 个人信息、收货地址、实名认证

详细 API 文档见 [docs/apis/](docs/apis/)。

## 微信小程序

项目内含微信小程序 SDK（[docs/weapp/](docs/weapp/)），提供开箱即用的服务调用封装：

- 统一的 HTTP 请求客户端（带 Token 自动管理）
- 各模块 API 服务封装（auth、mall、finance、chain、content、campaign、user）
- 完整的辅助工具

## 区块链集成

- **多链适配**：Chain33、FISCO-BCOS 双链支持
- **合约管理**：合约部署、合约仓库、ABI 编解码
- **证书签发**：CSR 生成、KeyPair 管理、RSA 签名
- **链上网络**：网络连接管理、RPC 客户端、状态监控
- **地址管理**：多链地址生成和管理

## 安全

- **身份认证** - Sanctum Token 认证
- **授权策略** - 基于 Policy 的细粒度权限控制
- **敏感词过滤** - 内置敏感词管理与过滤服务
- **黑名单机制** - IP/用户级黑名单
- **API 日志** - 完整的 API 请求审计日志
- **限流保护** - API 频率限制
- **自定义验证规则** - 身份证、手机号、IP/CIDR、文件存在等

## 结算系统

支持多种结算计划配置：

- **按周期结算** - 按固定周期（日/周/月）自动结算
- **按条件结算** - 满足特定条件后触发结算
- **结算凭证** - 结算单、费用明细、对账

## 许可证

本项目采用 MIT 协议。详见 [LICENSE](LICENSE) 文件。

## 贡献

欢迎提交 Issue 和 Pull Request！

1. Fork 本仓库
2. 创建特性分支 (`git checkout -b feature/amazing-feature`)
3. 提交改动 (`git commit -m 'feat: add amazing-feature'`)
4. 推送到分支 (`git push origin feature/amazing-feature`)
5. 创建 Pull Request

## 致谢

- [Laravel](https://laravel.com)
- [Filament](https://filamentphp.com)
- [Livewire](https://livewire.laravel.com)
- [Horizon](https://laravel.com/docs/horizon)

---

<p align="center">Made with ❤️ using Laravel & Filament</p>
