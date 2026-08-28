# 随研笔记

## 用户注册耗时长的问题

```php
$count = 20;
$this->info('开始');

$progressBar = $this->output->createProgressBar($count);
$progressBar->start();
// 修改哈希算法的成本因子系数，越小越快
Config::set('hashing.bcrypt.rounds', 15);

$start = microtime(true);
for ($i = 0; $i < $count; $i++) {
    $user = User::create([
        'username' => fake('zh_CN')->phoneNumber(),
        'password' => bcrypt('123456'),
    ]);

    $user->profile->nickname = fake('zh_CN')->name();
    $user->profile->save();
    $progressBar->advance();
}
$progressBar->finish();
$this->newLine();
$this->info(sprintf('总耗时：%s毫秒', (microtime(true) - $start) * 1000));
```

## 性能优化建议

### 密码哈希优化

`.env` 中 `BCRYPT_ROUNDS` 默认值为 12。在大规模用户注册场景下成本因子可能成为性能瓶颈，可根据服务器性能调整：

| 成本因子 | 哈希时间（约） | 安全性 | 适用场景 |
|---------|---------------|--------|---------|
| 8 | 50ms | 较低 | 开发环境、测试环境 |
| 10 | 200ms | 中等 | 批量造数场景 |
| 12 | 800ms | 较高 | 生产环境（默认） |
| 15 | 10s+ | 极高 | 不建议用于用户注册 |

> 注意：`BCRYPT_ROUNDS` 修改后需 `php artisan config:cache` 生效。

### 批量操作优化

对于大量数据创建操作，建议使用批量插入：

```php
// 使用事务减少提交次数
DB::transaction(function () {
    $users = [];
    for ($i = 0; $i < 100; $i++) {
        $users[] = [
            'username' => fake('zh_CN')->phoneNumber(),
            'password' => bcrypt('123456'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    User::insert($users);
});
```

### 异步处理

将非关键业务逻辑放入队列异步处理：

```php
// 用户创建后自动派发事件（Model 的 $dispatchesEvents）
protected $dispatchesEvents = [
    'created' => UserCreatedEvent::class,
];

// 监听事件，执行非关键逻辑（如欢迎通知、初始化数据）
// 事件本身不入队列，可在 Listener 中 dispatch 队列 Job
```

### 索引优化

确保数据库表有适当的索引，避免全表扫描：

```php
// 常用查询字段添加索引
$table->index(['username', 'email']);
$table->unique('username');
$table->unique('email');
```

## 调试技巧

### 性能分析

使用 Laravel 的调试工具分析性能瓶颈：

```php
use Illuminate\Support\Facades\DB;

// 开启查询日志
DB::enableQueryLog();

// 执行代码...

// 查看查询日志
dd(DB::getQueryLog());
```

### 缓存优化

对于频繁访问的数据，使用缓存减少数据库查询：

```php
use Illuminate\Support\Facades\Cache;

$users = Cache::remember('users:list', 3600, function () {
    return User::where('status', 1)->get();
});
```

---

**最后更新**: 2026-08-28
**作者**: Development Team
