# 通知通道（Channels）

Laravel 通知系统的自定义发送通道集合。通知类负责**组装内容**，通道负责**发送**，两者通过 `toXxx()` 方法约定对接。

## 通道一览

| 通道 | 依赖包 | 状态 | 通知类需实现的方法 |
|---|---|---|---|
| `WechatOfficialChannel` | `overtrue/laravel-wechat` ✅ 已安装 | 可用 | `toWechatOfficial(Authenticatable $user): WechatOfficialMessage` |
| `WechatMiniChannel` | `overtrue/laravel-wechat` ✅ 已安装 | 需补配置 | `toWechatMini(Authenticatable $user): WechatMiniMessage` |
| `SmsChannel` | `overtrue/easy-sms` ✅ 已安装 | 可用 | `toSms(Authenticatable $user): array` |
| `TenantChannel` | 无（Filament 内置） | 可用 | `toTenant(Tenant $tenant): FilamentNotification` |
| `DingTalkChannel` | `alibabacloud/dingtalk` ⬜ 待安装 | 代码就绪，待装包 + 配置 | `toDingTalk(Authenticatable $user): DingTalkMessage` |
| `JPushChannel` | `jpush/jpush` ⬜ 待安装 | 代码就绪，待装包 + 配置 | `toJPush(Authenticatable $user): JPushMessage` |

安装命令：

```bash
composer require alibabacloud/dingtalk
composer require jpush/jpush
```

> `mingyoung/dingtalk` 曾是钉钉 SDK 的主流选择，但 Packagist 已标记为 **abandoned**（最后发布 2021 年），不建议使用。
> 钉钉亦可直接使用 Laravel HTTP 客户端调用 REST 接口，接口数量少时依赖更轻。

**注意**：钉钉与极光的通道代码已按官方 SDK 写好，但两个包尚未 `composer require`，
因此在装包之前这两个通道一旦进入真实发送路径会抛 `Class not found`。
配置缺失、接收人为空等前置校验会先于 SDK 调用执行，所以在装包前也能拿到清晰的报错提示。

---

## 通用机制

### 通知基类

所有通知类继承 `App\Contracts\Notification\BaseNotification`，它已实现 `ShouldQueue`：

```php
abstract class BaseNotification extends Notification implements ShouldQueue
{
    public string $connection = 'redis';          // 队列连接
    public int $tries = 3;                        // 最大尝试次数
    public array $backoff = [10, 60, 300];        // 重试间隔（秒）

    abstract public function via(Authenticatable $user): array;
}
```

### 派发方式

```php
public function via(Authenticatable $user): array
{
    return ['database', SmsChannel::class, JPushChannel::class];
}
```

通道可直接写类名，Laravel 会从容器解析，无需注册 ServiceProvider。

### 消息契约

| 契约 | 类型 | 说明 |
|---|---|---|
| `Contracts\Notification\WechatOfficialMessage` | 具体类 | 链式构建器，`make()->openId()->templateId()->payload()` |
| `Contracts\Notification\WechatMiniMessage` | 接口 | 需自行实现 `getTemplateId/getData/getPage/getToUser` |
| `Contracts\Notification\DingTalkMessage` | 空类 | **待补齐**，建议参考 `WechatOfficialMessage` 补成构建器 |
| `Contracts\Notification\JPushMessage` | 空类 | **待补齐**，同上 |

统一约定：通道内用 `method_exists($notification, 'toXxx')` 判断，不支持时抛 `InvalidArgumentException`；
`toXxx()` 返回空值时通道直接 `return`，静默跳过（便于按用户偏好动态关闭某个通道）。

---

## 1. 微信公众号 `WechatOfficialChannel`

**发送内容**：模板消息（service account template message）

### 实现步骤

1. 确认 `.env` 已配置 `WECHAT_OFFICIAL_ACCOUNT_APPID / SECRET / TOKEN / AES_KEY`。
2. 通知类实现 `toWechatOfficial()` 返回 `WechatOfficialMessage`：

```php
use App\Contracts\Notification\WechatOfficialMessage;

public function via(Authenticatable $user): array
{
    return [WechatOfficialChannel::class];
}

public function toWechatOfficial(Authenticatable $user): ?WechatOfficialMessage
{
    $openid = $user->socialites()
        ->where('provider', SocialiteProvider::WeChat)
        ->value('provider_id');

    if (!$openid) {
        return null;   // 未绑定公众号，静默跳过
    }

    return WechatOfficialMessage::make()
        ->openId($openid)
        ->templateId('H3n7xB2C0U_RgO1fzsyxI1WZ6KKlM6qmVEuCc6n55W0')
        ->url(url('/user/orders/'.$this->order->no))
        ->payload('first', '您的订单已支付')
        ->payload('keyword1', $this->order->no)
        ->payload('keyword2', '¥'.$this->order->getTotalAmount())
        ->payload('remark', '感谢您的购买');
}
```

3. 通道调用 `POST /cgi-bin/message/template/send` 下发。

### 注意事项

- `data` 的键是**模板占位符名**（`first`、`keyword1`、`remark`），需与公众号后台模板一致。
- `client_msg_id` 目前用 `time()`，同一秒内会重复，建议改 `Str::uuid()`。
- `miniprogram` 未设置时会传空数组 `[]`，微信会报参数错误，应仅在需要小程序跳转时携带。
- 仅**服务号**支持模板消息，且要求用户与公众号有过交互（关注/授权）。
- 常见错误码：`40003` openid 无效、`40037` template_id 无效、`43004` 未关注、`45047` 调用超限。

---

## 2. 微信小程序 `WechatMiniChannel`

**发送内容**：订阅消息（subscribe message）

### 实现步骤

1. **放开配置**：`config/easywechat.php` 中 `mini_app` 段落当前是注释状态，需取消注释并补充：

```php
'mini_app' => [
    'default' => [
        'app_id' => env('WECHAT_MINI_APP_APPID', ''),
        'secret' => env('WECHAT_MINI_APP_SECRET', ''),
    ],
],
```

2. 通知类实现 `toWechatMini()`，返回实现 `WechatMiniMessage` 接口的对象。
3. 通道调用 `POST /cgi-bin/message/subscribe/send` 下发。

### 注意事项

- **多租户改造**：小程序密钥在生产上是租户级的，存在 `wechat_minis` 表
  （见 `App\Models\Foundation\WechatMini`）。`MiniProgramController` 的写法是
  `new Application(['app_id' => ..., 'secret' => ...])` 动态构造。
  当前通道读全局配置 `config('easywechat.mini_app.default')`，多租户下应改为动态构造。
- `data` 的键必须是**模板字段名 + 序号**（`thing1`、`time2`、`amount3`），
  且 value 长度受字段类型限制（`thing` 类型 20 字符以内），超长报 `47003`。
- **订阅配额**：一次性订阅需用户在小程序内主动授权，每次授权只能发一条，
  服务端必须记录配额（建议新增表按 `openid + template_id` 计数，发送后 -1）。
  长期订阅仅对特定行业类目开放。
- 当前实现 `catch (Exception $e) {}` 静默吞异常，建议改为 `report($e)`，
  并按错误码区分：`43101`（用户未订阅）属业务正常，不应重试；其他错误应抛出交由队列重试。
- openid 来源：`socialites.provider_id`（provider = `WeChat`）；
  若公众号与小程序绑定同一开放平台，可用 `union_id` 关联。

---

## 3. 短信 `SmsChannel`

**依赖**：`overtrue/easy-sms`（已安装），配置见 `config/easy-sms.php`。

### 实现步骤

1. **代码已实现**：`SmsService::send(string $phone, array $message)` 为通用发送方法，
   `sendCode()` 内部改为调用它，`SmsChannel` 通过构造函数注入 `SmsService` 复用。
2. 通知类实现 `toSms()`，返回结构与 `EasySms::send()` 的 message 参数一致：

```php
public function toSms(Authenticatable $user): array
{
    return [
        'content' => '您的订单已完成配送',
        'template' => '订单配送通知',
        'data' => ['order_no' => $this->order->no],
    ];
}
```

3. 手机号取 `$user->username`（`users` 表无独立 phone 字段，手机号即登录名，
   见 `MiniProgramController` 中 `'username' => $phoneNumber`）。取不到时抛
   `InvalidArgumentException`。

### 注意事项

- 阿里云等网关要求模板必须先在服务商后台**报备**，`template` 填模板名称而非模板 ID。
- 网关策略为 `OrderStrategy`（顺序调用，前一个失败自动降级到下一个），默认 `debug` → `aliyun`。
- `config/easy-sms.php` 的 `debug` 开关打开时不真正发短信，本地开发无需真实密钥。
- 短信发送失败抛 `NoGatewayAvailableException`；验证码接口已挂 `sms` 限流器
  （见 `AppServiceProvider`），通知类短信建议业务侧另行控制频率。

---

## 4. 租户通知 `TenantChannel`

**依赖**：无，基于 Filament 内置通知组件。

与其他通道的**关键区别**：notifiable 是 `Tenant` 而不是用户，不走 Laravel 的 ChannelManager，
由业务侧显式调用。

```php
use App\Channels\TenantChannel;

class NewOrderToTenant extends BaseNotification
{
    public function via(Authenticatable $user): array
    {
        return [TenantChannel::class];
    }

    public function toTenant(Tenant $tenant): FilamentNotification
    {
        return FilamentNotification::make()
            ->title('您有已付款订单请处理')
            ->body(sprintf('订单编号：%s，付款金额：%s', $this->order->no, $this->order->getTotalAmount()))
            ->success()
            ->actions([Action::make('toViewPage')->label('查看订单')->url(...)]);
    }
}
```

通道内执行 `$notify->sendToDatabase($tenant->administrators)`，写入 `notifications` 表。

### 注意事项

- 当前实现为「发给租户下全部管理员」。管理员较多时 `sendToDatabase()` 会逐条插入，
  建议按 `chunk()` 分批，或改为写租户级汇总通知后由管理员拉取。
- `toTenant()` 返回的是 `Filament\Notifications\Notification`，
  与 Laravel 的 `Illuminate\Notifications\Notification` 不是同一个类，注意别引错命名空间。
- 如需实时提醒，可额外调用 `$notify->broadcast()`（需配置广播驱动，项目当前未启用）。

---

## 5. 钉钉 `DingTalkChannel`

**依赖**：`alibabacloud/dingtalk`（阿里云官方 SDK，覆盖新版开放平台全部接口）

**发送内容**：工作通知（机器人单聊消息）

### 实现步骤

1. **配置**（尚未写入 `config/services.php`，上线前按需补上）：

```php
'dingtalk' => [
    'app_key' => env('DINGTALK_APP_KEY'),
    'app_secret' => env('DINGTALK_APP_SECRET'),
    'robot_code' => env('DINGTALK_ROBOT_CODE'),   // 未配置时回退到 app_key
],
```

2. 通知类实现 `toDingTalk()` 返回 `DingTalkMessage`，**接收人由通知类解析后写入消息**：

```php
use App\Contracts\Notification\DingTalkMessage;
use App\Enums\Foundation\SocialiteProvider;

public function via(Authenticatable $user): array
{
    return [DingTalkChannel::class];
}

public function toDingTalk(Authenticatable $user): ?DingTalkMessage
{
    $userId = $user->socialites()
        ->where('provider', SocialiteProvider::DingTalk)
        ->value('provider_id');

    if (!$userId) {
        return null;   // 未绑定钉钉，静默跳过
    }

    return DingTalkMessage::make()
        ->userId($userId)
        ->markdown('订单通知', "### 您有新的订单\n\n订单号：{$this->order->no}");
}
```

`DingTalkMessage` 提供的方法：

| 方法 | 说明 |
|---|---|
| `make()` | 创建实例 |
| `userId(string)` / `userIds(array)` | 设置接收人（自动去重，单次最多 100 个） |
| `text(string)` | 文本消息（`sampleText`） |
| `markdown(string $title, string $text)` | Markdown 消息（`sampleMarkdown`） |
| `payload(string $msgKey, array $msgParam)` | 自定义消息类型 |

3. 通道内部流程：校验配置 → 取 `access_token`（`Cache` 缓存 `expireIn - 300` 秒）→
   调 `batchSendOTOWithOptions()` 发送 → 记录部分失败（无效用户 / 被流控）。

### 注意事项

- **配置校验先于 SDK 调用执行**，所以配置缺失时拿到的是清晰的 `RuntimeException`，
  而不是 SDK 层面的报错。
- `msgParam` 由通道统一 `json_encode` 为字符串（SDK 要求 JSON 字符串而非数组）。
- `invalidStaffIdList`（无效用户）与 `flowControlledStaffIdList`（被流控）只记 `warning`
  日志，不重试——这些属于业务侧数据问题，重试无意义。
- 钉钉 userId 需提前绑定：可在 `socialites` 表写入 `provider = 'DingTalk'` 的记录
  （注意 `App\Enums\Foundation\SocialiteProvider` 枚举目前还没有 `DingTalk` case，需补一个），
  或在 `users` 表新增 `dingtalk_userid` 字段。
- 群机器人 Webhook 方式走另一套接口（`oapi.dingtalk.com/robot/send`），
  无需 `access_token`，本通道未覆盖。

---

## 6. 极光推送 `JPushChannel`

**依赖**：`jpush/jpush`（极光官方 PHP SDK，v3.7.0，命名空间 `JPush\`，仅需 `ext-curl`）

### 实现步骤

1. **配置**（尚未写入 `config/services.php`，上线前按需补上）：

```php
'jpush' => [
    'app_key' => env('JPUSH_APP_KEY'),
    'master_secret' => env('JPUSH_MASTER_SECRET'),
    'apns_production' => env('JPUSH_APNS_PRODUCTION', env('APP_ENV', 'production') === 'production'),
],
```

2. 通知类实现 `toJPush()` 返回 `JPushMessage`：

```php
use App\Contracts\Notification\JPushMessage;

public function toJPush(Authenticatable $user): JPushMessage
{
    return JPushMessage::make()
        ->alert('您有新的订单待处理')
        ->title('订单通知')
        ->extras(['order_no' => $this->order->no]);
}
```

3. 通道内部流程：`new Client($appKey, $masterSecret, storage_path('logs/jpush.log'))`
   → `push()->setPlatform()->setNotificationAlert()` → 设置推送目标 →
   `androidNotification()` / `iosNotification()` → `options()` → `send()`。

`JPushMessage` 提供的方法：

| 方法 | 说明 |
|---|---|
| `make()` | 创建实例 |
| `alert(string)` | 通知提示文案（必填，为空抛 `InvalidArgumentException`） |
| `title(?string)` | 通知标题，缺省取 `config('app.name')` |
| `platform(array\|string)` | 推送平台，默认 `['android', 'ios']`，可传 `'all'` |
| `alias(array\|string)` | 别名推送 |
| `registrationIds(array)` | 设备标识推送 |
| `extras(array)` | 附加数据，客户端可读取用于跳转 |
| `options(array)` | 透传极光可选参数 |

### 推送目标解析规则

1. 设置了 `registrationIds()` → 按设备标识推送；
2. 否则按别名推送，别名缺省为 `(string) $user->getKey()`。

客户端 SDK 调用 `setAlias` 时需使用同一值（用户主键）。一个用户多设备时，
客户端对同一别名重复 `setAlias`，极光会自动维护多设备关系。

### 注意事项

- **`apns_production` 必须显式设置**：SDK 的 `options()` 在未传入该参数时会强制置为
  `false`（走 APNs 开发环境，生产包收不到），因此通道始终按配置注入。
- SDK 的 `addAlias()` 与 `addAllAudience()` 互斥，且未设置任何 audience 时
  `build()` 会抛异常，因此通道必须始终落到一个明确目标上。
- 发送失败抛 `JPush\Exceptions\JPushException`，通道内 `report()` 后继续抛出，
  交由队列重试（`BaseNotification::$tries = 3`、`$backoff = [10, 60, 300]`）。
- 同一 AppKey 的推送接口有 QPS 限制，超限返回 `429`；
  大批量推送前应确认是否需要限速或改用定时任务（`$client->schedule()`）。

---

## 环境变量清单

> 以下钉钉 / 极光变量尚未写入 `config/services.php` 与 `.env.example`，
> 上线前按需补上配置段落即可，通道代码已按 `config('services.xxx.yyy')` 读取。

```dotenv
# 微信公众号（已在 .env.example 中）
WECHAT_OFFICIAL_ACCOUNT_APPID=
WECHAT_OFFICIAL_ACCOUNT_SECRET=
WECHAT_OFFICIAL_ACCOUNT_TOKEN=
WECHAT_OFFICIAL_ACCOUNT_AES_KEY=

# 微信小程序（需补充到 .env.example）
WECHAT_MINI_APP_APPID=
WECHAT_MINI_APP_SECRET=

# 短信（已在 config/easy-sms.php 中引用）
EASY_SMS_DEBUG=true
EASY_SMS_CODE_LENGTH=4
EASY_SMS_ALIYUN_ACCESS_KEY_ID=
EASY_SMS_ALIYUN_ACCESS_KEY_SECRET=

# 钉钉（待补配置）
DINGTALK_APP_KEY=
DINGTALK_APP_SECRET=
DINGTALK_ROBOT_CODE=

# 极光推送（待补配置）
JPUSH_APP_KEY=
JPUSH_MASTER_SECRET=
JPUSH_APNS_PRODUCTION=
```

---

## 已知问题

- `App\Jobs\Campaign\SendRedpackJob::getWechatOpenid()` 调用了 `$user->socialites()`，
  但 `User` 模型上未定义该关联，运行时会抛 `BadMethodCallException`。
  需在 `App\Models\User\User` 中补上：

```php
/**
 * 关联三方账号
 *
 * @return HasMany<Socialite>
 */
public function socialites(): HasMany
{
    return $this->hasMany(Socialite::class);
}
```

  同时 `App\Enums\Foundation\SocialiteProvider` 枚举缺 `DingTalk` case，使用钉钉通道前需补上。

- 钉钉 / 极光的 SDK 尚未安装，两个通道进入真实发送路径会抛 `Class not found`，
  装包后即可正常工作。
- `WechatOfficialChannel` 的 `client_msg_id` 使用 `time()`，同一秒内会重复，
  微信侧以此做去重，可能导致消息被丢弃，建议改为 `Str::uuid()`。
