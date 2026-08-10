# SmsGateways

调试/开发用短信网关，拦截短信发送而不实际投递，用于本地开发和测试。

## 核心类

| 类 | 职责 |
|----|------|
| `DebugGateway` | 继承 EasySms Gateway，`send()` 返回内容而非真实发送 |

## 使用方式

`SmsService` 在运行时动态注册此网关：

```php
$easySms->extend('debug', fn (array $config) => new DebugGateway($config));
```

当 `config('easy-sms.debug')` 为 `true` 时，所有短信发送被拦截并返回：

```php
[
    'to' => '13800138000',
    'content' => '您的验证码是 123456',
    'template' => 'SMS_123456',
    'data' => ['code' => '123456'],
]
```

## 配置

`config/easy-sms.php`：

```php
'debug' => true,  // 开发环境设为 true
```

## 依赖

- `overtrue/easy-sms` — 短信发送框架
