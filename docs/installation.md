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

- PHP >= 8.5
- Composer
- Node.js & NPM
- MySQL / PostgreSQL / SQLite
- Redis (推荐)

## 安装步骤

### 1. 创建项目

```bash
composer create jason/saas myProject -vvv --ignore-platform-reqs 
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

### 3. 环境配置

复制环境变量配置文件：

```bash
cp .env.example .env
```

生成应用密钥并配置数据库：

```bash
php artisan key:generate
```

在 `.env` 文件中配置数据库连接：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
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

### 6. 构建前端资源（如适用）

```bash
npm install
npm run build
```

---

## 本地开发

### 启动开发服务器

```bash
php artisan serve
```

### 监听文件变化

```bash
npm run dev
```

或使用 Composer：

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

### 1. 安装优化依赖

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

### 2. 生成优化配置

```bash
# 配置缓存
php artisan config:cache

# 路由缓存
php artisan route:cache

# 视图缓存
php artisan view:cache

# 生成优化文件
php artisan optimize
php artisan filament:optimize
```

### 3. 队列服务（如使用队列）

启动 Laravel Horizon：

```bash
php artisan horizon
```

### 4. 定时任务

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
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 生成缓存
php artisan config:cache
php artisan route:cache
php artisan view:cache
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
php artisan test

# 运行特定测试
php artisan test --filter=TestName

# 运行覆盖率测试
php artisan test --coverage
```

---

## 故障排查

### Vite 资源加载失败

如果遇到 "Unable to locate file in Vite manifest" 错误：

```bash
npm run build
```

或在开发环境中：

```bash
npm run dev
```

### 权限问题

确保 Web 服务器用户对 `storage` 和 `bootstrap/cache` 目录有写权限。

### 缓存问题

如果配置更改不生效，尝试清除所有缓存：

```bash
php artisan optimize:clear
```

---

## 升级指南

升级依赖后，执行以下步骤：

```bash
composer update
php artisan migrate
php artisan optimize:clear
php artisan optimize
```
