# Saas.Foundation

基于 **Laravel 13** 和 **Filament 5** 构建的企业级 SaaS 基础框架。

[![Laravel Version](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Filament Version](https://img.shields.io/badge/Filament-5-FCB900?style=flat-square)](https://filamentphp.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.5-777BB4?style=flat-square&logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

## 简介

Saas.Foundation 提供了一套完整的 SaaS 应用基础架构，包含：

- 🏗️ **多租户架构** - 内置租户管理和隔离机制（运营端 + 租户端双面板）
- 🎨 **现代化后台** - 基于 Filament 5 的精美管理面板，8 大功能集群
- 🔐 **权限控制** - 完善的 RBAC 角色和权限管理系统
- 📦 **模块化设计** - 功能按业务域模块化拆分，独立可维护
- ⚡ **高性能** - Laravel Octane 与 Horizon 队列支持
- 🔔 **多渠道通知** - 钉钉、极光推送、微信小程序/公众号、短信等多通道通知
- 🔗 **区块链集成** - 合约管理、证书签发、链上地址管理
- 💰 **财务体系** - 账户资金、支付、发票、退款、凭证、计划任务

## 模块概览

| 模块                         | 说明                    | 运营端 | 租户端 |
|----------------------------|-----------------------|-----|-----|
| 🏢 **租户管理** (User/Setting) | 租户生命周期、管理员、角色权限       | ✅   | ✅   |
| 🛍️ **商城** (Mall)          | 商品、订单、购物车、品牌、属性、配送、退款 | ✅   | ✅   |
| 💰 **财务** (Finance)        | 资金账户、支付、发票、退款、凭证、结算计划 | ✅   | ✅   |
| 📄 **内容管理** (Content)      | 内容发布、分类、评论、通知、敏感词     | ✅   | ✅   |
| 🎯 **营销活动** (Campaign)     | 优惠券、红包码               | ✅   | ✅   |
| 🔗 **区块链** (BlockChain)    | 合约部署、证书签发、网络连接管理      | ✅   | ✅   |
| 🔌 **基础服务** (Foundation)   | 支付宝、阿里云、微信支付、社交登录     | ✅   | ✅   |
| ⚙️ **系统设置** (Setting)      | 系统配置、队列监控、导入导出、API 日志 | ✅   | ✅   |

## 快速开始

### 环境要求

- PHP >= 8.5
- Composer
- Node.js & NPM
- MySQL / PostgreSQL / SQLite
- Redis（推荐）

### 安装

```bash
# 安装依赖
composer install --no-dev -vvv --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix

# 配置环境变量
cp .env.example .env
php artisan key:generate

# 初始化数据库
php artisan migrate
php artisan db:seed

# 启动开发服务器
php artisan serve
```

## 通知系统

支持多渠道消息推送，统一接口：

| 渠道       | 通道类                     | 说明         |
|----------|-------------------------|------------|
| 🔔 钉钉    | `DingTalkChannel`       | 钉钉机器人/工作通知 |
| 📱 极光推送  | `JPushChannel`          | APP 推送     |
| 💬 微信小程序 | `WechatMiniChannel`     | 小程序订阅消息    |
| 📢 微信公众号 | `WechatOfficialChannel` | 公众号模板消息    |
| 📨 短信    | `SmsChannel`            | 短信验证码/通知   |
| 🏢 租户内通知 | `TenantChannel`         | 站内信        |

## 技术栈

| 组件       | 版本  | 说明     |
|----------|-----|--------|
| Laravel  | v13 | 核心框架   |
| Filament | v5  | 管理面板   |
| Livewire | v4  | 动态组件   |
| Horizon  | v5  | 队列监控   |
| Octane   | v2  | 高性能服务  |
| Sanctum  | v4  | API 认证 |
| PHP      | 8.5 | 运行环境   |

## 项目结构

```
Saas.Foundation
├── app/
│   ├── Channels/              # 通知渠道（钉钉、极光、微信、短信等）
│   ├── Console/               # 命令行命令
│   ├── Contracts/             # 接口定义（通知、支付、结算等）
│   ├── Enums/                 # 枚举类型（按模块组织）
│   ├── Filament/              # Filament 面板
│   │   ├── Actions/           #   通用 Action
│   │   ├── Backend/           #   运营端（Clusters, Resources, Pages）
│   │   ├── Tenant/            #   租户端（Clusters, Resources, Pages）
│   │   ├── Forms/             #   自定义表单组件
│   │   ├── Infolists/         #   自定义信息列表组件
│   │   └── Tables/            #   自定义表格组件/过滤器
│   ├── Http/                  # HTTP 相关（控制器、认证、中间件等）
│   │   ├── Controllers/       #   按模块组织（Auth, Mall, Finance 等）
│   │   └── Middleware/        #   中间件
│   ├── Jobs/                  # 队列任务
│   ├── Models/                # Eloquent 模型（按模块组织）
│   ├── Policies/              # 授权策略（按模块组织）
│   ├── Providers/             # 服务提供者
│   └── Services/              # 业务服务层（按模块组织）
├── config/                    # 配置文件
├── database/                  # 迁移、工厂、填充
├── routes/                    # 路由定义
├── resources/                 # 视图、资源文件
└── storage/                   # 存储目录
```

## 开发指南

### 运行测试

```bash
# 运行所有测试
php artisan test

# 运行特定测试
php artisan test --filter=TestName
```

### 代码格式化

项目使用 Laravel Pint 进行代码格式化：

```bash
vendor/bin/pint --dirty --format agent
```

### 常用命令

```bash
# 清除缓存
php artisan optimize:clear

# 优化应用
php artisan optimize
php artisan filament:optimize

# 查看路由
php artisan route:list

# 队列工作
php artisan horizon
```

## 安全

本框架内置多项安全特性：

- **身份认证** - Sanctum API 认证 + 多因素认证支持
- **授权策略** - 基于 Policy 的细粒度权限控制
- **敏感词过滤** - 内置敏感词管理与过滤服务
- **黑名单机制** - IP/用户级黑名单
- **API 日志** - 完整的 API 请求审计日志
- **限流保护** - API 频率限制

## 许可证

本项目采用 MIT 开源协议。详见 [LICENSE](LICENSE) 文件。

## 贡献

欢迎提交 Issue 和 Pull Request！

## 致谢

感谢以下优秀的项目：

- [Laravel](https://laravel.com)
- [Filament](https://filamentphp.com)
- [Livewire](https://livewire.laravel.com)

---

<p align="center">Made with ❤️ using Laravel & Filament</p>
