#!/bin/bash

chown -R 1000:1000 storage bootstrap

cp .env.example .env

# 更新 Composer 依赖
echo "Updating Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 初始化环境，数据库
php artisan key:generate
php artisan migrate
php artisan db:seed

# 清除缓存
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 生成优化的配置文件
echo "Generating optimized config file..."
php artisan config:cache

# 生成优化的路由文件
echo "Generating optimized route file..."
php artisan route:cache

# 生成优化的视图文件
echo "Generating optimized view file..."
php artisan view:cache

# 生成优化的类加载文件
echo "Generating optimized class loader..."
php artisan optimize
php artisan filament:optimize

echo "Deployment completed successfully!"
