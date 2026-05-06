# 配置说明

## 应用配置

### .env 文件

主要配置项：

```env
APP_NAME=JasonSaaS
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=saas
DB_USERNAME=postgres
DB_PASSWORD=

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 多租户配置

```env
TENANCY_WEBSITE_ROUTE=true
TENANCY_DOMAIN_ROUTE=false
```

## 数据库配置

### PostgreSQL

推荐使用 PostgreSQL 作为主数据库，支持更好的并发性能和 JSON 类型。

### Redis

Redis 用于：
- 缓存
- 队列驱动
- Session 存储
- Horizon 监控

## 权限配置

权限定义在 `app/Policies` 目录下，通过 `#[PolicyName]` 注解自动注册。

### 创建新权限

1. 在对应的 Policy 中添加方法
2. 使用 `#[PolicyName('权限名称')` 注解
3. 在 Action 中通过 `userCan()` 辅助函数检查权限

## Horizon 配置

```env
HORIZON_SIGNAL=unix
```

Horizon 相关配置在 `config/horizon.php` 中。