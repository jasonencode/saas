# Filesystem

扩展 Laravel Storage，注册阿里云 OSS 自定义磁盘驱动，提供完整的 Flysystem 适配器。

## 架构

```
JasonFilesystem::boot()  ← AppServiceProvider::boot()
    └── Storage::extend('oss', ...)
            └── OssAdapter
                    └── CoreAdapter (抽象基类)
```

## 目录结构

```
Filesystem/
├── JasonFilesystem.php          # 启动类，注册 OSS 驱动
└── Adapters/
    ├── CoreAdapter.php          # 抽象基类，配置校验 + getUrl()
    └── OssAdapter.php           # 阿里云 OSS 适配器
```

## 核心类

| 类 | 职责 |
|----|------|
| `JasonFilesystem` | 启动时注册 `oss` 磁盘驱动 |
| `CoreAdapter` | 抽象基类，校验配置格式，提供 CDN URL 生成 |
| `OssAdapter` | 完整 Flysystem 操作：读写、删除、复制、目录管理、临时 URL |

## 使用方式

在 `AppServiceProvider::boot()` 中自动注册，之后直接使用：

```php
use Illuminate\Support\Facades\Storage;

// 写入文件
Storage::disk('oss')->put('avatars/1.jpg', $fileContent);

// 读取文件
$content = Storage::disk('oss')->get('avatars/1.jpg');

// 获取 CDN URL
$url = Storage::disk('oss')->url('avatars/1.jpg');

// 获取临时签名 URL（有效期 3600 秒）
$url = Storage::disk('oss')->temporaryUrl('avatars/1.jpg', now()->addSeconds(3600));
```

## 配置

`config/filesystems.php`：

```php
'disks' => [
    'oss' => [
        'driver' => 'oss',
        'key' => env('OSS_ACCESS_KEY'),
        'secret' => env('OSS_SECRET_KEY'),
        'region' => env('OSS_REGION'),
        'bucket' => env('OSS_BUCKET'),
        'endpoint' => env('OSS_ENDPOINT'),
        'url' => env('OSS_URL'),       // CDN URL（可选）
        'throw' => false,
    ],
],
```

## 依赖

- `aliyuncs/oss-sdk-php` — 阿里云 OSS SDK
- `league/flysystem` — 文件系统抽象
