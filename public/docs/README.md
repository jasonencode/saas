# JasonSaaS

<div class="badge">Modern SaaS Foundation</div>

JasonSaaS 是一个基于 Laravel 和 Filament 构建的现代化 SaaS 基座，提供开箱即用的多租户架构和完善的权限系统。

## 特性

- **多租户架构**：支持多租户隔离，适配各种 SaaS 场景
- **权限系统**：基于 Policy 的精细化权限控制
- **现代化 UI**：基于 Filament 和 TailwindCSS 的优雅界面
- **快速开发**：预配置的业务模块，开箱即用

## 技术栈

| 技术 | 版本 |
|------|------|
| Laravel | 13 |
| Filament | 5 |
| Livewire | 4 |
| TailwindCSS | 4 |
| PostgreSQL | - |
| Redis | - |

## 快速开始

```bash
# 克隆项目
git clone https://github.com/jasonencode/saas.git

# 安装依赖
composer install
pnpm install

# 复制环境配置文件
cp .env.example .env

# 生成应用密钥
php artisan key:generate

# 运行数据库迁移
php artisan migrate

# 启动开发服务器
php artisan serve
```

## 业务模块

项目采用 **Cluster (集群)** 模式组织 Filament 资源：

| 模块 | 说明 |
|------|------|
| **BlockChain** | 区块链模块 - 地址、证书、合约、网络管理 |
| **Campaign** | 营销活动模块 - 优惠券、红包管理 |
| **Content** | 内容管理模块 - 内容、评论、分类、敏感词 |
| **Finance** | 财务管理模块 - 账户、支付、发票、退款 |
| **Foundation** | 基础配置模块 - 微信、支付宝、阿里云等配置 |
| **Mall** | 商城管理模块 - 商品、订单、供应商等 |
| **User** | 用户管理模块 - 用户、租户、Token管理 |

## 文档导航

- [安装指南](getting-started/installation) - 详细的安装步骤
- [配置说明](getting-started/configuration) - 环境配置指南
- [多租户](core/multi-tenancy) - 多租户架构说明
- [权限系统](core/permissions) - 权限控制详解
- [Filament 使用](guide/filament) - Filament 资源结构和使用指南

## License

MIT License