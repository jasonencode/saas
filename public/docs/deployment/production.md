# 生产环境部署

## 环境要求

| 要求 | 版本 |
|------|------|
| PHP | 8.5+ |
| Node.js | 18+ |
| PostgreSQL | 14+ |
| Redis | 6+ |
| Nginx/Apache | 最新稳定版 |

## 服务器配置

### Nginx 配置

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/saas/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 环境变量

生产环境 `.env` 配置：

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=saas
DB_USERNAME=postgres
DB_PASSWORD=your_secure_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

HORIZON_SIGNAL=unix
```

## 部署步骤

### 1. 代码部署

```bash
cd /var/www/saas
git pull origin main
composer install --no-dev --optimize-autoloader
pnpm build
```

### 2. 配置优化

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. 数据库迁移

```bash
php artisan migrate --force
```

### 4. Horizon 启动

```bash
php artisan horizon:terminate
php artisan horizon
```

### 5. 队列监控（可选）

使用 Supervisor 监控 Horizon：

```ini
[program:saas-horizon]
process_name=%(program_name)s
command=php /var/www/saas/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/saas/storage/logs/horizon.log
stopwaitsecs=3600
```
