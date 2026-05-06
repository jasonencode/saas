# 安装指南

## 环境要求

| 要求 | 版本 |
|------|------|
| PHP | 8.5+ |
| Node.js | 18+ |
| PostgreSQL | 14+ |
| Redis | 6+ |

## 安装步骤

### 1. 克隆项目

```bash
git clone https://github.com/jasonencode/saas.git
cd saas
```

### 2. 安装 PHP 依赖

```bash
composer install
```

### 3. 安装前端依赖

```bash
pnpm install
```

### 4. 配置环境变量

```bash
cp .env.example .env
```

编辑 `.env` 文件，配置数据库和 Redis 连接：

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=saas
DB_USERNAME=postgres
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 5. 生成应用密钥

```bash
php artisan key:generate
```

### 6. 运行数据库迁移

```bash
php artisan migrate
```

### 7. 启动开发服务器

```bash
php artisan serve
```

访问 `http://localhost:8000` 查看应用。

## Horizon 监控

安装完成后，可以访问 Horizon 监控面板：

```env
HORIZON_SIGNAL=unix
```

启动 Horizon：

```bash
php artisan horizon
```

访问 `http://localhost:8000/horizon` 查看监控面板。