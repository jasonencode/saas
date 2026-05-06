# 快速开始

## 介绍

JasonSaaS 是一个现代化的 SaaS 基础框架，基于 Laravel 13 和 Filament 5 构建。

## 核心特性

### 多租户架构

内置多租户支持，通过 `app\Models\Traits\IsTenant` trait 实现租户隔离。

### 权限系统

基于 Laravel Policy 的权限控制，支持细粒度的操作权限管理。

### Filament 管理面板

预配置的 Filament 资源和管理页面，快速构建后台管理系统。

## 项目结构

```
├── app/
│   ├── Filament/          # Filament 资源定义
│   ├── Models/            # 数据模型
│   ├── Policies/          # 权限策略
│   └── Services/          # 业务服务
├── config/                # 配置文件
├── database/              # 数据库迁移和种子
├── public/docs/           # 文档（Docsify）
├── resources/
│   └── views/             # 视图文件
├── routes/                # 路由定义
└── tests/                 # 测试文件
```

## 下一步

- [安装指南](getting-started/installation) - 安装项目
- [配置说明](getting-started/configuration) - 配置项目
- [多租户](core/multi-tenancy) - 了解多租户