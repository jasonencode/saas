# 部署与安装指南

本文档包含 Saas.Foundation 项目的详细安装和部署步骤。

## 目录

- [环境要求](#环境要求)
- [安装步骤](#安装步骤)
- [本地开发](#本地开发)
- [生产环境部署](#生产环境部署)
- [常用命令](#常用命令)

---

## 环境要求

- PHP >= 8.5（需 `bcmath`、`openssl`、`pdo_pgsql`、`zip`、`gmp` 等扩展）
- Composer
- Node.js & pnpm
- PostgreSQL 14+
- Redis 6+（缓存、队列、Session）

## 安装步骤

### 1. 获取项目

```bash
git clone https://github.com/jasonencode/saas.git
cd saas
```

或作为依赖创建项目：

```bash
composer create jason/saas myProject -vvv
```

### 2. 安装依赖

#### Windows 平台

由于 Windows 不支持某些 POSIX 扩展，需要忽略相关依赖：

```bash
composer install -vvv --no-dev --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix
```

更新依赖：

```bash
composer update -vvv --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix
```

#### Linux/Unix 平台

```bash
composer install
```

前端依赖使用 pnpm：

```bash
pnpm install
pnpm build
```

### 3. 环境配置

复制环境变量配置文件：

```bash
cp .env.example .env
```

生成应用密钥并配置数据库：

```bash
php artisan key:generate
```

在 `.env` 文件中配置数据库连接（默认 PostgreSQL）：

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=saas
DB_USERNAME=postgres
DB_PASSWORD=your_password

SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=
REDIS_PORT=6379
```

### 4. 初始化数据库

```bash
php artisan migrate
php artisan db:seed
```

### 5. 设置文件夹权限

#### Linux/Unix

```bash
chown -R www:www storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

#### Windows

确保 IIS_IUSRS 或 Web 服务器用户组对以下文件夹有读写权限：
- `storage`
- `bootstrap/cache`

### 6. 构建前端资源

```bash
pnpm install
pnpm build
```

> 一键完成安装：`composer run setup`（依赖安装 + `.env` 生成 + 密钥 + 迁移 + 前端构建）。

---

## 本地开发

### 启动开发服务器

```bash
php artisan serve
```

### 监听文件变化

```bash
pnpm dev
```

或使用 Composer（并行启动 artisan dev 与 vite）：

```bash
composer run dev
```

### 清除缓存

开发过程中可能需要清除缓存：

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 生产环境部署

> 完整的 Nginx / Supervisor 配置见 [生产环境部署](../deployment/production)。

### 1. 安装优化依赖

```bash
composer install --no-dev --optimize-autoloader --no-interaction
pnpm build
```

### 2. 生成优化配置

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
```

### 3. 数据库迁移

```bash
php artisan migrate --force
```

### 4. 队列服务

启动 Laravel Horizon（通过 Supervisor 常驻，见部署文档）：

```bash
php artisan horizon
```

### 5. 定时任务

配置 Cron：

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 常用命令

### 开发相关

```bash
# 启动开发服务器
php artisan serve

# 运行队列
php artisan queue:work

# 运行 Horizon
php artisan horizon

# 查看路由列表
php artisan route:list

# 查看配置
php artisan config:show app.name
```

### 缓存管理

```bash
# 清除所有缓存
php artisan optimize:clear

# 生成缓存
php artisan optimize
```

### 优化命令

```bash
# 优化自动加载
composer dump-autoload --optimize

# 优化应用
php artisan optimize
php artisan filament:optimize
```

### 数据库相关

```bash
# 运行迁移
php artisan migrate

# 回滚迁移
php artisan migrate:rollback

# 重置迁移
php artisan migrate:reset

# 刷新迁移
php artisan migrate:refresh

# 填充数据
php artisan db:seed

# 迁移并填充
php artisan migrate --seed
```

### 测试

```bash
# 运行所有测试
php artisan test --compact

# 运行特定测试
php artisan test --compact --filter=TestName
```

---

## 故障排查

### Vite 资源加载失败

如果遇到 "Unable to locate file in Vite manifest" 错误：

```bash
pnpm build
```

或在开发环境中：

```bash
pnpm dev
```

### 权限问题

确保 Web 服务器用户对 `storage` 和 `bootstrap/cache` 目录有写权限。

### 缓存问题

如果配置更改不生效，尝试清除所有缓存：

```bash
php artisan optimize:clear
```

### 前端改动不生效

确认执行过 `pnpm build`（或开发时 `pnpm dev` 在运行）。

---

## 升级指南

升级依赖后，执行以下步骤：

```bash
composer update
pnpm install
php artisan migrate
php artisan optimize:clear
php artisan optimize
```

### Nginx 伪静态配置注意事项

确保 `location` 块包含以下内容：livewire 文件预览，如果开启了浏览器缓存，会缓存文件，导致文件预览失败。

```
location ^~ /livewire {
    try_files $uri $uri/ /index.php?$query_string;
}
```
