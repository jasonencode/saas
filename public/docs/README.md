# Saas.Foundation

<div class="badge">Modern SaaS Foundation</div>

Saas.Foundation 是一个基于 Laravel 13 和 Filament 5 构建的现代化 SaaS 基座，提供开箱即用的多租户架构、完善的权限系统和预置业务模块，可作为独立 SaaS 产品或小程序后端的起点。

## 特性

- **多租户架构**：平台 / 租户双面板，租户数据隔离，支持按域名识别租户
- **权限系统**：基于 Laravel Policy + `#[PolicyName]` 注解的精细化按钮级权限控制
- **分层架构**：Controller / Service / Job 分层，统一 `ServiceInterface` 契约与 `service()` 助手
- **业务模块**：商城、营销活动、内容、财务、区块链、基础配置等预置模块
- **通知系统**：数据库通知 + 短信 / 微信小程序 / 微信公众号 / 钉钉 / 极光推送等多通道
- **现代化 UI**：Filament 5 Cluster 集群模式 + TailwindCSS 4 的优雅界面

## 技术栈

| 技术 | 版本 |
|------|------|
| PHP | 8.5 |
| Laravel | 13 |
| Filament | 5 |
| Livewire | 4 |
| TailwindCSS | 4 |
| Vite | 8 |
| PostgreSQL | 14+ |
| Redis | 6+ |

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

# 构建前端资源
pnpm build

# 启动开发服务器
php artisan serve
```

## 业务模块

项目采用 **Cluster (集群)** 模式组织 Filament 资源，所有集群位于 `app/Filament/Backend/Clusters/`：

| 模块 | 说明 |
|------|------|
| **BlockChain** | 区块链模块 - 地址、证书、合约、合约仓库、网络管理 |
| **Campaign** | 营销活动模块 - 优惠券、红包、抽奖管理 |
| **Content** | 内容管理模块 - 内容、评论、分类、标签、单页、应用版本 |
| **Finance** | 财务管理模块 - 账户、支付、发票、凭证、结算计划与任务 |
| **Foundation** | 基础配置模块 - 微信、支付宝、阿里云、社交登录等配置 |
| **Mall** | 商城管理模块 - 商品、订单、退款、店铺、配送等 |
| **Setting** | 系统管理模块 - 管理员、角色、API 日志、黑名单、失败任务、Horizon 监控 |
| **User** | 用户管理模块 - 用户、租户、身份、实名、员工、Token 管理 |

## 文档导航

- [安装指南](getting-started/installation) - 详细的安装步骤
- [配置说明](getting-started/configuration) - 环境配置指南
- [多租户](core/multi-tenancy) - 多租户架构说明
- [权限系统](core/permissions) - 权限控制详解
- [API 总览](guide/api) - 各模块 API 接口索引
- [Filament 使用](guide/filament) - Filament 资源结构和使用指南

## License

MIT License
