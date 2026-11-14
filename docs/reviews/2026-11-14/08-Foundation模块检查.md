# Foundation 模块 检查报告

- **检查日期**：2026-11-14
- **检查范围**：`app/Models/Foundation/**`、`app/Services/Foundation/**`、`app/Support/{AES,RSA,SmsGateways,Filesystem,helpers.php}`、`app/Enums/Foundation/**`、Foundation 相关迁移、`app/Filament/Forms/Foundation/**` 与 Foundation 资源、`tests/Feature/System/**`、`tests/Unit/**` 相关用例
- **检查方式**：逐文件走读 + 上传/加密/短信/微信链路推演 + 第三方依赖源码交叉核对
- **结论**：第三方凭据落库已改为 `encrypted`（旧 FND-03 修复一半）；但 **AES 仍是 CBC 无 MAC、无版本/轮换**，**上传无大小限制且沿用客户端扩展名**，**短信调试网关默认开启**。新增的 `upload_files` + `UploadFileExistsRule` 方向正确，但存在「规则只查元数据」「路径回填二次查询未带 user_id」「规则注释示例参数错位」等残留问题。

**统计：严重 1 / 高 1 / 中 6 / 低 2（共 10 条）。**

---

## 一、模块概览

| 子域 | 现状 | 主要风险 |
|---|---|---|
| 加密 | AES-256-CBC、RSA-OAEP | 无 MAC/版本，篡改与密钥轮换风险 |
| 上传 | 内容哈希命名、私有/公开双盘、`upload_files` 记录 | 无大小上限、客户端扩展名、可见性客户端自选 |
| 短信 | easy-sms + debug 网关 | 调试模式默认开，验证码固定 |
| 微信/支付宝/阿里云 | 微信支付 v3 已接入；其余多为半成品 | `getConfig()` 空实现、表单明文密钥 |
| 文件 URL | 临时签名 + 永久 URL 回退 | 私有盘异常时给出永久链接 |

---

## 二、严重问题

### FND-01 ｜ 严重 ｜ 短信 / 弱随机性

**短信通道默认调试模式，验证码固定 `0000`**

- **位置**：`config/easy-sms.php:11,31-40,47-50`、`app/Support/SmsGateways/DebugGateway.php:29-40`、`app/Services/Foundation/SmsService.php:89-98`
- **失败场景**：部署即失去 OTP 随机性；DebugGateway 还会把含验证码的内容写入日志侧。
- **修复建议**：与 USER-01 同修——默认 `false`、移除 debug、生产环境断言。

---

## 三、高优先级问题

### FND-02 ｜ 高 ｜ 加密

**AES-256-CBC 无完整性校验、单密钥无版本**

- **位置**：`app/Support/AES/AES.php:13,30-47,58-91,100-108`
- **代码要点**：格式 `base64(iv)|base64(ciphertext)`，无 HMAC/tag；key 恒为 `hash('sha256', config('custom.aes_key'))`；解密失败还区分多种错误分支。
- **失败场景**：密文可位翻转/填充预言篡改，错误分支给攻击者区分信号；身份证密文随 API 下发（USER-07）放大攻击面；密钥轮换后历史数据不可解。
- **修复建议**：改 AES-256-GCM 或 sodium secretbox，格式纳入版本与 tag；解密失败统一异常；`user_realnames` 增 `key_version`。

---

## 四、中优先级问题

### FND-03 ｜ 中 ｜ 密钥展示

**第三方支付/云凭据表单明文展示且编辑强制重填（落库已加密）**

- **位置**：`app/Filament/Forms/Foundation/WechatPaymentForm.php:33-47`、`AlipayForm.php:32-35`、`AliyunForm.php:25-29`、`app/Filament/Tenant/Clusters/Foundation/Resources/WechatPayments/WechatPaymentResource.php:34-37`
- **失败场景**：`->password()->revealable()` 可点开明文；`private_key/platform_cert/public_key` 用纯 Textarea 直接显示解密值；有编辑权的员工可抄走商户私钥。
- **修复建议**：`password()->revealable(false)->dehydrated(fn ($state) => filled($state))`，空值表示不修改；回显仅显示「已配置」；密钥页限定平台超管 + 审计。

### FND-04 ｜ 中 ｜ 上传安全

**上传无大小限制、沿用客户端扩展名、无配额/租户目录**

- **位置**：`app/Http/Requests/UploadRequest.php:18-25`、`UploadsRequest.php:18-23`、`app/Services/Foundation/UploadService.php:36-44`
- **失败场景**：`max:` 被注释；`getClientOriginalExtension()` 直接拼文件名，JPEG 内容 + `shell.php` 文件名可通过 `mimes` 并以 `.php` 落到公开盘（storage 误配解析即 RCE）；限流只限次数不限体积。
- **修复建议**：恢复 `max:` 并加网关限制；扩展名白名单；目录带 tenant/user；`upload_files` 补外键与租户维度。

### FND-05 ｜ 中 ｜ 上传 / 实名链路

**实名上传链路：只校验元数据不校验实体、路径回填二次查询不带 `user_id`、规则注释示例参数错位**

- **位置**：`app/Rules/UploadFileExistsRule.php:20,31-34,51-59`、`app/Http/Controllers/User/RealnameController.php:96-114`
- **代码要点**：规则只 `UploadFile::whereKey($uuid)->exists()`；`resolveUploadFilePaths()` 另起查询不过滤 `user_id`；`if ($user = Auth::user())` 为 null 时不做归属过滤；规则构造首参是 `?string $message`，注释示例却写 `new UploadFileExistsRule(FileVisibility::Private)`（实际需命名参数）。
- **失败场景**：文件被维护任务/人工删除后规则仍通过，落库一个不存在的路径；规则若用于未登录/服务端上下文，任意 UUID 都会被接受；照注释示例调用会 TypeError；校验与回填之间存在 TOCTOU。
- **修复建议**：回填同样 `where('user_id', Auth::id())` 并断言取到路径；未登录直接拒绝；可选校验 `Storage::disk($disk)->exists($path)`；修正注释示例。

### FND-06 ｜ 中 ｜ 隐私

**上传可见性由客户端决定且默认 Public，证件照可能落入公开盘**

- **位置**：`app/Http/Controllers/System/UploadController.php:30,46`、`app/Enums/Foundation/FileVisibility.php:28-33`、`UploadRequest.php:26`
- **失败场景**：前端漏传 `visibility` 时身份证照片写入公开桶，拿到 URL 即可长期访问；提交实名会因磁盘校验失败被拒，但公开副本已存在。
- **修复建议**：私有用途由服务端决定可见性，或按用途白名单限制；对 public 上传做频次与体积上限。

### FND-07 ｜ 中 ｜ 半成品集成

**支付宝/微信公众号/小程序/社交账号 `getConfig()` 为空实现**

- **位置**：`app/Models/Foundation/Alipay.php:37-40`、`Wechat.php:49-54`、`WechatMini.php:37-42`、`SocialiteAccount.php:35-40`、`AlipayService.php:24-33`
- **失败场景**：后台可维护支付宝配置但无任何支付入口；一旦接线必然抛配置异常；微信配置靠 Service 直读字段绕过 `getConfig()`。
- **修复建议**：补齐 `getConfig()` 并接入流程，或删除相关模型/资源/服务。

### FND-08 ｜ 中 ｜ 微信测试配置

**微信「测试配置」错误处理错误，正常配置也大概率失败**

- **位置**：`app/Services/Foundation/WechatService.php:24-41`、`app/Filament/Actions/Foundation/TestWechatConnection.php:28-42`
- **失败场景**：未取 access_token 调 `cgi-bin/user/get`；catch 里 `substr($error, strpos(...))` 无 `{` 时 `strpos=false`，随后 `json_decode` 抛未捕获 JsonException；errcode 被当作异常码。
- **修复建议**：用稳定接口/`getAccessToken()` 验证；解析前判断 JSON；失败用统一业务异常，`is_connected` 仅成功时置 true。

---

## 五、低优先级问题

### FND-09 ｜ 低 ｜ 加密工具（死代码）

**RSA「长文本分块」OAEP 分块尺寸算错**

- **位置**：`app/Support/RSA/RSA.php:152-175,187-214,306-309`
- **说明**：OAEP 分支按 keyBytes 分块，实际为 `keyBytes − 2*hashLen − 2`；RSA 类全仓无调用方。
- **修复建议**：按 padding 精确计算，或改「RSA 加密随机 AES key + AES 加密正文」；无计划则删除。

### FND-10 ｜ 低 ｜ 文件 URL

**私有文件 URL 回退为永久 URL；OSS 临时签名失败返回 false 触发类型错误**

- **位置**：`app/Support/helpers.php:109-122`、`app/Support/Filesystem/Adapters/OssAdapter.php:455-481`
- **失败场景**：私有盘不支持临时 URL 时返回永久链接（实名照片/发票 PDF）；OSS 失败返回 false 而 `temporary_file_url(): ?string` 会 TypeError 500。
- **修复建议**：不支持临时 URL 时抛异常或走应用内签名路由；OSS 失败抛异常，helper 做 `is_string` 判断。

---

## 六、旧问题状态（2026-11-01 → 2026-11-14）

| 旧编号 | 状态 | 说明 |
|---|---|---|
| FND-01 固定验证码 | 仍存在 | 同 USER-01 |
| FND-02 AES-CBC 无 MAC | 仍存在 | 无 tag、无版本 |
| FND-03 密钥明文 | 部分修复 | 落库已 `encrypted`；表单仍 revealable/Textarea |
| FND-04 上传限制/文件名 | 仍存在 | `max:` 仍注释、客户端扩展名 |
| FND-05 RSA 分块 | 仍存在 | 无调用方 |
| FND-06 私有 URL 回退 | 仍存在 | 另发现 OSS false→TypeError |
| FND-07 支付宝空实现 | 仍存在 | `getConfig()` 返回 `[]` |
| FND-08 微信临时证书清理 | 有变化（风险未消除） | `initPayment()` 在 try 外，异常时临时文件不清理 |
| FND-09 微信退款回调 | 有变化 | 已移除 `openssl_free_key`；回调仍缺验签失败分支 |
| FND-10 密钥落库 | 大部分已修 | Aliyun/Wechat/Socialite/WechatPayment 均 `encrypted` |

**新增**：FND-05 上传规则/回填细节、FND-06 可见性默认公开。

---

## 七、修复优先级

1. **P0**：FND-01（固定验证码）。
2. **P1**：FND-02（加密完整性）、FND-04（上传 RCE/DoS）、FND-05（实名照片链路）。
3. **P2**：FND-03（密钥展示）、FND-06（可见性）、FND-08（微信测试）。
4. **P3**：FND-07/09/10。
