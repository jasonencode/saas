# Saas.Foundation

基于 **Laravel 13** 和 **Filament 5** 构建的企业级 SaaS 基础框架。

[![Laravel Version](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Filament Version](https://img.shields.io/badge/Filament-5-FCB900?style=flat-square)](https://filamentphp.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-777BB4?style=flat-square&logo=php)](https://php.net)
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
- 🤖 **AI 助手** - Filament 面板内嵌 AI 对话助手，支持多 Agent、流式回复、消息落库

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
| 🤖 **AI 助手** (Ai) | 订单查询 / 日报 Agent、工具调用、会话落库 | ✅ | ✅ |

## 技术栈

| 组件 | 版本 | 说明 |
|------|:----:|------|
| Laravel | v13 | 核心框架 |
| Filament | v5 | 管理面板 |
| Livewire | v4 | 动态组件 |
| Laravel AI | v1.3 | AI Agent / 工具调用 |
| Filament Chat Assistant | v2 | 面板 AI 助手 |
| Horizon | v5 | 队列监控 |
| Sanctum | v4 | API 认证 |
| PostgreSQL | 18 | 默认数据库 |
| Redis | 8 | 缓存、队列、Session |
| PHP | 8.4+ | 运行环境（本机 8.5） |

## 项目结构

```
Saas.Foundation
├── app/
│   ├── Ai/                    # AI Agent 与工具（app/Ai/Agents、app/Ai/Tools）
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
│   ├── Livewire/              # Livewire 组件（Homepage）
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
│   ├── development/           #   开发说明与实现方案
│   ├── flows/                 #   业务状态流转图
│   ├── reports/               #   排查/修复报告
│   └── todos/                 #   待办与规划
├── lang/                      # 多语言（zh_CN、zh_TW、en）
├── public/
│   └── docs/                  # 面板内帮助文档（guide/）
├── resources/                 # 视图、CSS、JS 资源
├── routes/                    # 路由
│   ├── api.php                #   API 路由
│   ├── apis/                  #   模块化 API 路由（auth、mall、finance 等）
│   ├── web.php                #   Web 路由
│   └── console.php            #   Artisan 路由
├── stubs/                     # 自定义 stub 模板
├── storage/                   # 存储目录
├── tests/                     # 测试（Feature、Unit）
├── scripts/                   # Composer 钩子脚本（PHP 8.5 弃用修补等）
├── composer.json
├── pint.json                  # Laravel Pint 配置
└── vite.config.js             # Vite 构建配置
```

## 快速开始

### 环境要求

- PHP >= 8.4（本机使用 8.5）
- 扩展：bcmath、dom、gmp、libxml、mbstring、openssl、pdo、zip
- Composer 2、Node.js 20+、pnpm
- PostgreSQL 18（默认）与 Redis 8；也支持 MySQL / SQLite
- Windows 上 Horizon 依赖的 `pcntl` / `posix` 不可用，安装依赖时需要忽略这两个扩展

### 本地安装

```bash
# 1. 克隆项目
git clone <repo-url>
cd Saas.Foundation

# 2. 安装 PHP 依赖（Windows 缺少 pcntl/posix）
composer install --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix

# 3. 安装前端依赖并构建
pnpm install
pnpm build

# 4. 环境变量
cp .env.example .env
php artisan key:generate
# 编辑 .env：数据库 / Redis 连接、BACKEND_DOMAIN、TENANT_DOMAIN 等

# 5. 初始化数据库（包含 laravel/ai 的会话表）
php artisan migrate --seed
```

> 也可以直接执行 `composer setup`：安装依赖、复制 `.env`、生成 key、迁移数据库并构建前端。**Windows 下不要用它**（内部 `composer install` 未忽略 `pcntl/posix`），按上面的步骤操作即可。

### 本地开发

项目支持 `.env.local` 作为**整套**本地环境（不是覆盖层）：

```bash
# 只有 --env=local（或进程 APP_ENV=local）时才会读取 .env.local
php artisan serve --env=local --port=8182

# 前端热更新
pnpm dev
```

- 本地 `.env.local` 需设置 `FORCE_HTTPS=false`，否则资源会走 https 导致白屏
- 若依赖服务跑在 Docker 容器里（容器名 `postgresql` / `redis`），可在 hosts 添加 `127.0.0.1 postgresql`，或直接把 `PG_DB_HOST` / `REDIS_HOST` 指向 `127.0.0.1`
- `composer dev` 会并行启动 Horizon、`artisan serve` 和 Vite；Windows 没有 `pcntl/posix`，Horizon 起不来，建议分别运行 `php artisan serve` 与 `pnpm dev`

### 生产安装

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

## 开发指南

### 运行测试

```bash
# 全量测试（先清理配置缓存再跑）
composer test

# 单个文件 / 单个方法
php artisan test --compact tests/Feature/Mall/OrderLifecycleApiTest.php
php artisan test --compact --filter=testName
```

> AI 助手包（`jason/filament-chat-assistant`）的测试请在它自己的仓库中运行 `vendor/bin/phpunit`。

- 测试固定使用 SQLite `:memory:`、`QUEUE_CONNECTION=sync`、`CACHE_STORE=array`
- 部分 PostgreSQL 专用代码（`split_part`、`jsonb @>`）在 SQLite 下会失败，改动这些路径需在 PostgreSQL 上验证
- PHP 8.5 下 `clue/block-react` 的弃用警告已由 `scripts/patch-block-react.php` 在 `composer install/update` 后自动修补

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

## AI 助手

面板右下角的 AI 对话助手由 [`jason/filament-chat-assistant`](https://packagist.org/packages/jason/filament-chat-assistant) 提供（`^2.0`），通过 Composer 从 Packagist 安装。

- **Agent 配置**：`config/chat-assistant.php`，按面板分组（`backend` / `tenant`），当前两端都启用「订单查询」「订单日报」
- **Agent 实现**：`app/Ai/Agents`，工具在 `app/Ai/Tools`
- **会话落库**：Agent 使用 `laravel/ai` 的 `RemembersConversations`，消息写入 `agent_conversations` / `agent_conversation_messages`
- **包文档**：[GitHub](https://github.com/jasonencode/filament-chat-assistant) / [Packagist](https://packagist.org/packages/jason/filament-chat-assistant)（中英文 README）

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
