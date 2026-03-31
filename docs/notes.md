# 随研笔记

## 用户注册耗时长的问题

```php
$count = 20;
$this->info('开始');

$progressBar = $this->output->createProgressBar($count);
$progressBar->start();
# 修改哈希算法的成本因子系数，越小越快
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