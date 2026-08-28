# 生产环境部署

## 📋 概述

本文档描述 Saas.Foundation 的生产环境部署方案，包含 Nginx、PHP（Octane）、Supervisor（Horizon）、定时任务与域名规划。

## 🌐 域名规划

| 用途 | 环境变量 | 示例 | 路由 |
|------|----------|------|------|
| API | `API_DOMAIN` | `api.example.com` | `routes/apis/` 各模块 |
| 平台面板 | `BACKEND_DOMAIN` | `admin.example.com` | `/backend` |
| 租户面板 | `TENANT_DOMAIN` | `tenant.example.com` | `/tenant`，子域 `{slug}.tenant.example.com` |
| Horizon | `HORIZON_DOMAIN` / `HORIZON_PATH` | `/backend/horizon` | 平台面板内 |

`config/custom.php` 的 `domains` 数组由以上环境变量注入，`FORCE_HTTPS` 控制强制跳转。

## 🐘 基础设施

| 组件 | 要求 |
|------|------|
| PHP | 8.5（fpm 或 Octane） |
| PostgreSQL | 14+，中文全文搜索需 `zhparser` 扩展（见 [数据库](../guide/database)） |
| Redis | 6+，`REDIS_DB`（Horizon）与 `REDIS_CACHE_DB`（缓存）分开 |
| Node | 仅构建时 `pnpm build` |

## 📦 部署流程

```bash
# 1. 拉取代码
git pull

# 2. 安装生产依赖
composer install --no-dev --optimize-autoloader --no-interaction

# 3. 前端构建
pnpm install
pnpm build

# 4. 数据库迁移
php artisan migrate --force

# 5. 缓存与优化
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize

# 6. 目录权限
chown -R www:www storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## 🕸️ Nginx 配置

### 域名入口（以租户面板为例）

```nginx
server {
    listen 443 ssl http2;
    server_name ~^(?<tenant>.+)\.tenant\.example\.com$|tenant\.example\.com;

    root /var/www/saas/public;
    index index.php index.html;

    # 前端静态资源
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Livewire 文件预览（禁用缓存，否则文件预览失败）
    location ^~ /livewire {
        add_header Cache-Control "no-store, no-cache, must-revalidate";
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

> 各域名（API / 平台 / 租户）可复用同一配置模板，仅 `server_name` 不同。API 域名可关闭前端资源相关配置。

### 多租户域名解析

租户面板按 `server_name` 捕获子域名 `{slug}.tenant.example.com`，Filament 的 `IdentifyTenant` 中间件按 slug 识别租户（见 [多租户](../core/multi-tenancy)）。API 域名下租户由 `X-Tenant-Id` 请求头解析。

## 🚀 进程管理（Supervisor）

### Horizon 队列

```ini
[program:horizon]
process_name=%(program_name)s
command=php /var/www/saas/artisan horizon
directory=/var/www/saas
user=www
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/www/saas/storage/logs/horizon.log
stopwaitsecs=3600
stopasgroup=true
killasgroup=true
environment=SUPERVISOR_PROCESS_NAME="%(program_name)s",SERVER_ID="prod-1"
```

- `SERVER_ID` 用于区分多实例 Horizon 主进程（`HORIZON_NAME`）
- Horizon 面板在平台面板 `/backend/horizon` 访问，权限由 `viewHorizon` Gate 控制

### 队列 Worker（可选，Horizon 已覆盖）

Horizon 本身管理 worker 进程，一般无需额外 `queue:work` 进程；特殊队列可在 Horizon 的 supervisor 配置（`config/horizon.php`）中分配。

## ⏰ 定时任务

```bash
# crontab -e
* * * * * cd /var/www/saas && php artisan schedule:run >> /dev/null 2>&1
```

调度定义在 `routes/console.php`（`Schedule`），业务命令位于 `app/Console/Commands/`：

- 订单自动关闭 / 自动签收（`Mall` 组命令）
- 身份到期处理（`IdentityExpireCommand`）
- Horizon 快照（`horizon:snapshot`）
- 维护类命令（`Maintenance` 组）

## 🔧 常用运维命令

```bash
# 缓存一键清理
php artisan optimize:clear

# 队列状态
php artisan queue:work --once   # 单条试跑
php artisan horizon:pause      # 暂停队列
php artisan horizon:terminate  # 终止 worker

# 失败任务
php artisan queue:retry failed

# 配置检查
php artisan config:show app
php artisan route:list --except-vendor
```

## 📝 检查清单

部署前确认：

- [ ] `.env` 已配置 `API_DOMAIN` / `BACKEND_DOMAIN` / `TENANT_DOMAIN`
- [ ] `FORCE_HTTPS=true`
- [ ] `APP_DEBUG=false`、`APP_ENV=production`
- [ ] Redis 的 `REDIS_DB` 与 `REDIS_CACHE_DB` 已分离
- [ ] PostgreSQL 已创建租户子域解析（DNS 泛解析）
- [ ] Supervisor 已加载 `horizon` 程序
- [ ] crontab 已配置 `schedule:run`
- [ ] `php artisan filament:optimize` 已执行
- [ ] 日志目录 `storage/logs` 可写，并配置日志切割

## 🔍 故障排查

| 现象 | 处理 |
|------|------|
| 502 Bad Gateway | 检查 PHP-FPM / Octane 进程与 Nginx upstream |
| 队列堆积 | `horizon:pause` / 扩容 supervisor `max_processes` |
| 租户面板无法识别租户 | 检查子域 DNS 与 `TENANT_DOMAIN` 配置 |
| API 403 租户认证失败 | 检查 `X-Tenant-Id`、租户状态与过期时间（见 [租户 API 签名认证](../core/tenant-auth)） |
| 前端资源 404 | 重新 `pnpm build` 并确认 Nginx root 指向 `public/` |
