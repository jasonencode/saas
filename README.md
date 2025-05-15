# Laravel + Filament 【Saas 基座】

## 修改文件夹权限

```shell
chown -R www:www storage bootstrap/cache
```

## 正式环境安装

```shell
composer install --no-dev --optimize-autoloader --no-interaction
```

## 常用优化命令

```shell
# 初始化环境，数据库
php artisan key:generate
php artisan migrate
php artisan db:seed

# 清除缓存
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 生成优化的配置文件
php artisan config:cache

# 生成优化的路由文件
php artisan route:cache

# 生成优化的视图文件
php artisan view:cache

# 生成优化的类加载文件
php artisan optimize
php artisan filament:optimize
```

## 忽略windows平台依赖

```shell
composer update --no-dev -vvv --ignore-platform-reqs 
```
