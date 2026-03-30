# Saas.Foundation

基于 **Laravel 13** 和 **Filament 5** 构建的企业级 SaaS 基础框架。

[![Laravel Version](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Filament Version](https://img.shields.io/badge/Filament-5-FCB900?style=flat-square)](https://filamentphp.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.5-777BB4?style=flat-square&logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

## 简介

Saas.Foundation 提供了一套完整的 SaaS 应用基础架构，包含：

- 🏗️ **多租户架构** - 内置租户管理和隔离机制
- 🎨 **现代化后台** - 基于 Filament 5 的精美管理面板
- 🔐 **权限控制** - 完善的角色和权限管理系统
- 📦 **模块化设计** - 可扩展的模块化架构
- ⚡ **高性能** - Laravel Octane 支持，提升应用性能
- 🔔 **消息通知** - 多渠道通知系统（邮件、短信、推送等）

## 快速开始

### 环境要求

- PHP >= 8.5
- Composer
- Node.js & NPM
- MySQL / PostgreSQL / SQLite
- Redis (推荐)

### 安装

```bash
# 创建项目
composer create jason/saas myProject -vvv --ignore-platform-reqs 

# 进入项目目录
cd myProject

# 安装依赖(windows)
composer install -vvv --no-dev --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix

# 安装依赖(linux)
composer install -vvv --no-dev 

# 配置环境变量
cp .env.example .env
php artisan key:generate

# 初始化数据库
php artisan migrate
php artisan db:seed

# 启动开发服务器
php artisan serve
```

详细安装指南请参考：[安装文档](docs/installation.md)

## 核心特性

### 🏢 多租户系统
- 租户隔离和管理
- 租户级别配置
- 独立的租户数据空间

### 👥 用户与权限
- 基于角色的访问控制 (RBAC)
- 细粒度权限管理
- 策略模式授权

### 🛍️ 电商模块
- 商品和购物车管理
- 订单处理流程
- 支付集成接口

### 📊 管理面板
- Filament 5 构建的现代化后台
- 丰富的表格和表单组件
- 可视化数据展示

### 🔔 通知系统
- 多渠道通知（邮件、短信、钉钉、微信等）
- 可配置的通知模板
- 异步队列处理

### ⚙️ 技术栈

| 组件 | 版本 | 说明 |
|------|------|------|
| Laravel | v13 | 核心框架 |
| Filament | v5 | 管理面板 |
| Livewire | v4 | 动态组件 |
| Horizon | v5 | 队列监控 |
| Octane | v2 | 高性能服务 |
| Sanctum | v4 | API 认证 |

## 项目结构

```
Saas.Foundation
├── app/
│   ├── Channels/          # 通知渠道
│   ├── Console/           # 命令行命令
│   ├── Contracts/         # 接口定义
│   ├── Enums/             # 枚举类型
│   ├── Http/              # HTTP 相关（控制器、中间件等）
│   ├── Jobs/              # 队列任务
│   ├── Models/            # Eloquent 模型
│   ├── Policies/          # 授权策略
│   ├── Providers/         # 服务提供者
│   └── Services/          # 业务服务层
├── config/                # 配置文件
├── database/              # 迁移、工厂、填充
├── docs/                  # 项目文档
├── routes/                # 路由定义
├── resources/             # 视图、资源文件
└── storage/               # 存储目录
```

## 文档

- [📖 安装指南](docs/installation.md) - 详细的安装和部署步骤
- [📝 API 实现计划](docs/API_IMPLEMENTATION_PLAN.md) - API 设计规范
- [🛒 购物车模块](docs/cart.md) - 购物车功能说明
- [⚖️ 限流策略](docs/rate_limits.md) - API 限流配置
- [🏢 租户认证](docs/tenant-auth.md) - 租户认证流程

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

更多命令请参考：[部署文档](docs/installation.md#常用命令)

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
