# Foundation 模块 检查报告

- **检查日期**：2026-11-01
- **检查范围**：`app/Models/Foundation/*`、`app/Services/Foundation/*`、`app/Support/{AES,RSA,Filesystem,SmsGateways}/*`、`app/Http/Controllers/System/*`、`app/Http/Requests/Upload*`、Foundation 的 Filament 资源、`routes/apis/system.php`、Foundation 相关迁移
- **检查方式**：逐文件静态走读 + 加密/上传/短信链路推演
- **结论**：微信/支付宝/阿里云/小程序/社交账号的配置模型齐全，但**安全基础设施（AES、密钥存储、上传限制）偏弱**，且**支付宝链路是空实现**；短信调试网关默认开启是本期最严重的问题之一（已在 User 报告 USER-01 详述，此处补充平台层视角）。

**统计：严重 1 / 高 2 / 中 4 / 低 3（共 10 条）。**

---

## 一、模块概览

| 子域 | 文件 | 现状 |
|---|---|---|
| 加密 | `Support/AES/AES.php`、`Support/RSA/RSA.php` | AES-256-CBC 无 MAC；RSA OAEP 分块尺寸计算错误 |
| 文件 | `Support/Filesystem/*`、`Services/Foundation/UploadService.php`、`Requests/Upload*` | 无大小限制、沿用客户端扩展名、日志冗余 |
| 短信 | `Services/Foundation/SmsService.php`、`Support/SmsGateways/DebugGateway.php`、`config/easy-sms.php` | 调试网关默认开启、验证码固定 0000、无失败计数 |
| 微信 | `WechatPaymentService`、`WechatService`、`Wechat`/`WechatPayment`/`WechatMini` 模型 | 支付配置 `getConfig()` 完整；公众号服务可用 |
| 支付宝 | `AlipayService`、`Alipay` 模型 | **`getConfig()` 返回空数组，且无调用方，整体未实现** |
| 阿里云 | `Aliyun`/`AliyunDns`/`AliyunDomain`/`AliyunEcs` | 模型与后台资源齐全；AccessKey 明文存储 |
| 社交账号 | `Socialite`/`SocialiteAccount` | 模型齐全；无加密 |

---

## 二、严重问题

### FND-01 ｜ 严重 ｜ 认证 / 弱随机性（平台层）
**短信调试网关默认开启，验证码固定 `0000`**

- **位置**：`config/easy-sms.php:11`、`:34-45`、`app/Services/Foundation/SmsService.php:82-90`、`app/Support/SmsGateways/DebugGateway.php`
- **代码**：
  ```php
  'debug' => env('EASY_SMS_DEBUG', true),
  'default' => ['gateways' => ['debug', 'aliyun']],   // debug 优先
  'gateways' => ['debug' => ['code' => '0000']],
  ```
  ```php
  if (config('easy-sms.debug')) {
      return config('easy-sms.gateways.debug.code');
  }
  ```
- **失败场景**：按 `.env.example` 部署即进入调试模式，`generateCode()` 恒返回 `0000`。任何接入短信登录/找回密码/改绑手机号的入口都会变成「知道手机号即可登录」。
- **建议**：默认 `false`，从默认网关列表移除 `debug`；非测试环境强制 `random_int`；对固定码加启动断言。详见 User 报告 USER-01。

---

## 三、高优先级问题

### FND-02 ｜ 高 ｜ 密码学误用
**AES-256-CBC 无完整性校验、单一全局密钥、无版本/轮换**

- **位置**：`app/Support/AES/AES.php:13`、`:30-47`、`:100-108`
- **代码**：
  ```php
  private const string CIPHER = 'AES-256-CBC';
  $iv = random_bytes(self::ivLength());
  $ciphertext = openssl_encrypt($plaintext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv);
  return base64_encode($iv).self::SEPARATOR.base64_encode($ciphertext);
  ```
- **失败场景**：① CBC 无 HMAC/AEAD，密文可被位翻转/填充预言篡改，`decrypt()` 只校验 `false`，填充错误还会以不同异常暴露线索；② `key()` 永远是 `hash('sha256', config('custom.aes_key'))`，全库、全租户共用一把密钥，无 `key_version` 字段，轮换即历史数据不可解（注释已承认）；③ 身份证密文经 API 返回（User USER-07），攻击面被放大。
- **建议**：改用 `AES-256-GCM`（带 tag）或 `sodium_crypto_secretbox`，密文格式 `v1|iv|tag|ciphertext`；`user_realnames` 增 `key_version`，支持双密钥灰度轮换；解密失败统一抛同一种异常。

### FND-03 ｜ 高 ｜ 密钥管理
**第三方支付/云厂商密钥明文存储、表单明文展示**

- **位置**：`app/Models/Foundation/WechatPayment.php:54-64`、`WechatPaymentForm.php:27-39`、`Aliyun`/`Alipay` 模型
- **代码**：
  ```php
  return ['wechat' => ['default' => [
      'mch_id' => $this->mch_id,
      'mch_key_v3' => $this->secret,          // 明文
      'mch_secret_cert' => $this->private_key, // PEM 明文
      'mch_public_cert_path' => $publicCertPath,
  ]]];
  ```
  ```php
  Forms\Components\TextInput::make('secret')->label('支付秘钥V3')->required(),
  Forms\Components\Textarea::make('private_key')->label('API密钥')->rows(10)->required(),
  ```
- **失败场景**：`secret`（APIv3 密钥）、`private_key`（商户私钥）以明文存库；Filament 用普通 `TextInput/Textarea` 展示（非 `password()`、无「已配置」占位），任何能进入该页面的租户员工都能直接抄走。数据库备份、日志、`dd()` 都可能带出私钥，泄露即可伪造支付/退款请求。编辑时字段还是 `required()`，运营每次改配置都要重新粘贴密钥，进一步鼓励明文流转。
- **建议**：使用 Laravel `encrypted` cast（或 KMS）；表单改 `password()->revealable(false)->dehydrated(fn ($state) => filled($state))`，回显只显示 `****`；密钥页仅平台超管可见并审计。

---

## 四、中优先级问题

### FND-04 ｜ 中 ｜ 文件上传
**上传无大小限制、沿用客户端扩展名、每次上传写 info 日志**

- **位置**：`app/Http/Requests/UploadRequest.php:18-27`、`app/Services/Foundation/UploadService.php:33-40`、`app/Http/Controllers/System/UploadController.php:31`
- **代码**：
  ```php
  'file' => ['bail','required','file','image','mimes:jpg,jpeg,png,gif,bmp,webp',
      // 'max:'.config('storage.FRONT_MAX_FILE_SIZE'),   // 被注释
  ],
  ```
  ```php
  $name = sprintf('%s.%s', $hash, $file->getClientOriginalExtension());
  $disk->putFileAs($this->path, $file, $name, $visibility->value);
  ```
- **失败场景**：`max` 被注释，登录用户可反复灌入超大图片耗尽磁盘/带宽（仅 `throttle:uploads` 10 次/分钟，单次不限大小）。文件名用客户端扩展名：`mimes` 按内容 MIME 校验，因此「真图片 + 文件名 `shell.php`」能通过校验并以 `hash.php` 落到 public 盘；若 Web 服务器对 `storage/` 误配 PHP 解析（待确认部署方式），即为 RCE。`info($info)` 还把每次上传的路径/原始文件名写日志。
- **建议**：恢复 `max:`（如 5MB）并在 Nginx/PHP 双重限制；文件名改用 `$file->hashName()` 或强制白名单扩展名；删除 `info($info)` 或只记录 `uuid`；确保 `storage/` 不做 PHP 解析。

### FND-05 ｜ 中 ｜ 密码学误用
**RSA「长文本分块」的 OAEP 分块尺寸算错**

- **位置**：`app/Support/RSA/RSA.php:300-310`
- **代码**：
  ```php
  $keyBytes = intdiv((int) $details['bits'], 8);
  $maxPlain = ($padding === OPENSSL_PKCS1_PADDING) ? ($keyBytes - 11) : $keyBytes;
  ```
- **失败场景**：OAEP 最大明文 = keyBytes − 2×hashLen − 2（2048 位 + SHA-1 为 214 字节），代码却用 256；超过实际上限即 `openssl_public_encrypt` 失败。与注释「支持长文本分块」不符。
- **建议**：按 padding 精确计算；或改为「RSA 加密随机 AES 密钥 + AES 加密正文」的混合加密。当前 `RSA` 类无调用方，属未接线能力。

### FND-06 ｜ 中 ｜ 私有文件访问
**私有磁盘不支持临时 URL 时回退为永久公开 URL**

- **位置**：`app/Support/helpers.php:109-122`
- **代码**：
  ```php
  if ($disk->providesTemporaryUrls()) {
      return $disk->temporaryUrl($path, now()->addMinutes($minutes));
  }
  return $disk->url($path);
  ```
- **失败场景**：OSS/S3 私有盘若未正确实现 `temporaryUrl`（或配置了自定义适配器），`temporary_file_url()` 会返回永久可访问 URL，实名身份证照片、发票 PDF 等私有文件被公开。
- **建议**：不支持临时 URL 时直接抛异常，或走应用内签名路由（校验登录与归属）。

### FND-07 ｜ 中 ｜ 功能缺失 / 死代码
**支付宝链路是空实现**

- **位置**：`app/Models/Foundation/Alipay.php:28-31`、`app/Services/Foundation/AlipayService.php:24-32`
- **代码**：
  ```php
  public function getConfig(): array
  {
      return [];   // 空配置
  }
  ```
  ```php
  Pay::config($payment->getConfig());
  return Pay::alipay();   // 无配置必然失败
  ```
- **失败场景**：`AlipayService` 没有任何 Controller/Service 调用方，`Alipay::getConfig()` 返回空数组。后台可以维护「支付宝配置」，但前台没有任何支付宝支付入口；一旦有人接入，`Pay::config([])` + `Pay::alipay()` 会直接抛配置异常。`Wechat::getConfig()` / `WechatMini::getConfig()` 同样是空实现（目前靠 Service 直接读字段绕过）。
- **建议**：补齐 `getConfig()`（app_id、私钥、公钥、回调地址）或删除支付宝相关模型/资源/服务，避免「看起来支持、实际不可用」。

---

## 五、低优先级问题

| 编号 | 问题 | 位置 | 说明 |
|---|---|---|---|
| FND-08 | `WechatPayment` 临时证书文件依赖 `cleanupTempFiles()`，异常路径需确认 | `WechatPayment.php:62-80`、`WechatPaymentService.php:78-80` | 已在 `finally` 中清理，方向正确；若 `tempnam` 后 `file_put_contents` 失败会留下空文件。建议加 `try/finally` + 失败告警。 |
| FND-09 | `WechatPaymentService::sign()` 使用 `openssl_free_key()` | `WechatPaymentService.php:255-257` | PHP 8 已弃用/无实际作用，建议删除；`openssl_sign` 返回值未校验（失败时 `$signature` 为空字符串，仍返回 base64）。补 `if ($ok === false) throw`。 |
| FND-10 | 阿里云/社交账号凭据明文存储 | `Aliyun`/`Socialite`/`SocialiteAccount` 模型 | 与 FND-03 同类，建议统一 `encrypted` cast。 |

---

## 六、测试与验证缺口

- `UploadApiTest` 覆盖了基本上传，但**没有大小上限、扩展名伪造、私有文件签名**用例。
- 没有任何 AES/RSA 的单元测试（`Support/AES`、`Support/RSA` 无对应测试）。
- `WechatPaymentService` 的回调验签、退款、转账都没有测试（外部依赖，建议用 fake/契约测试）。

---

## 七、修复优先级

1. **P0（当天）**：FND-01（短信调试码）、FND-02（AES-GCM）。
2. **P1（本周）**：FND-03（密钥加密存储/表单脱敏）、FND-04（上传限制与文件名）。
3. **P2**：FND-05（RSA 分块）、FND-06（私有文件回退）、FND-07（支付宝接线或删除）。
4. **P3**：FND-08 ~ FND-10。
