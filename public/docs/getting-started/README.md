# 快速开始

## 介绍

Saas.Foundation 是一个现代化的 SaaS 基础框架，基于 Laravel 13 和 Filament 5 构建，采用 PostgreSQL + Redis 技术栈。

## 核心特性

### 多租户架构

内置多租户支持：

- **平台面板**（`/backend`）：平台方管理租户、配置全局业务
- **租户面板**（`/tenant`）：租户方管理自己店铺的业务数据，通过 `TENANT_DOMAIN` 域名识别租户
- 业务模型通过 `BelongsToTenant` trait 关联租户，配合 `ofTenant()` 作用域实现数据隔离
- API 请求通过 `X-Tenant-Id` 请求头解析当前租户（`TenantResolver`）

### 权限系统

基于 Laravel Policy 的权限控制：

- 每个模型通过 `#[UsePolicy]` 注解绑定策略类
- 策略方法用 `#[PolicyName('名称', type: PolicyType::Button)]` 注解定义权限名称
- Action / 页面通过 `userCan('ability', $record)` 辅助函数做按钮级鉴权
- 超级管理员（`Administrator::isAdministrator()`）在策略基类 `before()` 中直接放行

### Filament 管理面板

预配置 Filament Cluster 集群模式：`BlockChain`、`Campaign`、`Content`、`Finance`、`Foundation`、`Mall`、`Setting`、`User` 八个集群，资源目录结构统一，开箱即用。

### 分层架构

```
Controller (HTTP 层)  →  Service (业务层, ServiceInterface)  →  Model (数据层)
                                      ↓
                                 Job (异步任务, BaseJob)
```

## 项目结构

```
├── app/
│   ├── Channels/          # 通知通道（短信、微信小程序、钉钉等）
│   ├── Console/           # Artisan 命令
│   ├── Contracts/         # 契约接口（ServiceInterface、Policy 等）
│   ├── Enums/             # 枚举类（按业务模块分组）
│   ├── Events/            # 事件
│   ├── Filament/          # Filament 面板（Backend / Tenant）
│   ├── Http/              # 控制器、请求、资源、中间件
│   ├── Jobs/              # 队列任务
│   ├── Listeners/         # 事件监听器
│   ├── Models/            # 数据模型（按业务模块分组）
│   ├── Notifications/     # 通知类
│   ├── Policies/          # 权限策略
│   ├── Providers/         # 服务提供者
│   ├── Services/          # 业务服务
│   └── Support/           # 辅助函数、租户解析器、任务任务
├── config/                # 配置文件
├── database/              # 数据库迁移和种子
├── docs/                  # 开发文档（API、方案、脚本）
├── public/
│   └── docs/              # 本套文档（Docsify）
├── resources/             # 视图与样式
├── routes/                # 路由定义（routes/apis/ 下按模块拆分）
└── tests/                 # 测试文件
```

## 下一步

- [安装指南](installation) - 安装项目
- [配置说明](configuration) - 配置项目
- [多租户](../core/multi-tenancy) - 了解多租户
