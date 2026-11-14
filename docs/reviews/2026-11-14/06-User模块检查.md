# User 模块 检查报告

- **检查日期**：2026-11-14
- **检查范围**：`app/Models/User/**`、`app/Services/User/**`、`app/Http/Controllers/{Auth,User}/**`、`app/Http/Requests/{Auth,User}/**`、`app/Http/Resources/User/**`、`app/Rules/**`、`app/Policies/User/**`、`routes/apis/{auth,user}.php`、用户/认证相关迁移、`app/Filament/**` 中 User 资源与动作、`tests/Feature/{Auth,User}/**`
- **检查方式**：逐文件走读 + 关键链路（注册/登录/短信/实名/身份/令牌）推演 + 与现有测试比对
- **结论**：认证与会话仍是本期最薄弱环节——短信接口字段错配导致线上短信不可用、调试验证码固定 `0000`、用户令牌永不过期且改密不吊销、支付密码可无旧密码覆盖，四项均未修复。**实名照片改为「上传 UUID + 归属/磁盘校验」的链路方向正确**，旧「私有文件路径可被引用」已修复；残留是二次查询未带 `user_id`、可见性仍由客户端自选、规则只查元数据不查实体。

**统计：严重 1 / 高 5 / 中 7 / 低 1（共 14 条）。**

---

## 一、模块概览

| 子域 | 现状 | 主要风险 |
|---|---|---|
| 认证 | 用户名密码登录（图形验证码 + 限流）可用；短信/小程序/租户令牌链路不完整 | 固定 OTP、账号劫持、令牌不过期 |
| 短信 | 发送/校验服务存在 | 字段名错配，线上不可用 |
| 实名 | 提交/审核/状态查询齐全；照片改上传 UUID | PII 下发、权限绕过、二次查询未校验归属 |
| 身份 | 购买/授予/替换 | 静默替换旧身份、编号重复 |
| 推荐 | 绑定上级 | 可抢绑、无唯一约束、异常直出 |

---

## 二、严重问题

### USER-01 ｜ 严重 ｜ 认证 / 弱随机性

**短信调试网关默认开启，验证码恒为 `0000`**

- **位置**：`config/easy-sms.php:11,36-39,48-50`、`app/Services/Foundation/SmsService.php:89-98`
- **代码要点**：`'debug' => env('EASY_SMS_DEBUG', true)`，默认网关 debug 优先，`generateCode()` 直接返回固定码。
- **失败场景**：`.env.example` 未声明该变量，按示例部署不走随机数；一旦接入短信登录/找回密码，输入手机号 + `0000` 即可通过明文相等校验。
- **修复建议**：默认 `false`，默认网关移除 debug；非测试环境强制 `random_int`，对固定码加启动断言。

---

## 三、高优先级问题

### USER-02 ｜ 高 ｜ 功能缺陷

**短信发送接口校验 `mobile`、读取 `phone`，功能不可用且清空历史验证码**

- **位置**：`app/Http/Controllers/Auth/SmsController.php:23-25`、`app/Http/Requests/Auth/SmsSendRequest.php:18-21`、`SmsService.php:28-30`
- **失败场景**：`safe()->string('phone')` 取不到字段（只校验了 `mobile`）→ 空串；`sendCode('')` 先删旧码再调网关，用户永远收不到验证码。
- **修复建议**：统一字段名；补「发送成功后 `sms_codes` 落库」测试。

### USER-03 ｜ 高 ｜ 账号劫持

**小程序手机号登录可被「手机号用户名」抢注劫持，且取任意租户配置、不写 openid**

- **位置**：`app/Http/Controllers/Auth/MiniProgramController.php:28,45-55`、`RegisterRequest.php:17`、`PaymentController.php:195-201`、`WithdrawController.php:66-72`
- **失败场景**：攻击者先注册 `username=受害者手机号` 并设密码，受害者微信快捷登录落入攻击者账号；`Socialite/SocialiteAccount` 全仓无写入，微信支付/提现永远提示「请先绑定微信账号」。
- **修复建议**：以 `SocialiteAccount(provider, openid)` 为登录唯一键，手机号仅绑定；注册禁止纯手机号；`WechatMini` 查询带租户；登录补发 `Login` 事件并 upsert Socialite。

### USER-04 ｜ 高 ｜ 越权

**设置支付密码可在不知旧密码时覆盖**

- **位置**：`app/Http/Controllers/User/SafeController.php:78-90`、`app/Services/Finance/UserAccountService.php:238-243`
- **失败场景**：拿到任意有效会话后 `POST /api/user/safe/payment-password` 直接改掉支付密码，随后用于余额支付/提现。
- **修复建议**：`setPaymentPassword()` 判断已存在则拒绝；变更写 `EventLog` 并通知用户。

### USER-05 ｜ 高 ｜ 会话生命周期

**令牌永不过期、改密不吊销、小程序登录不写登录记录**

- **位置**：`AuthResponse.php:33`、`config/sanctum.php:53`、`SafeController.php:25-29`、`MiniProgramController.php:57`
- **失败场景**：登录一次即得永久全权令牌，改密后旧令牌仍可用；小程序登录不派发 `Login`，`/user/safe/records` 看不到微信登录。
- **修复建议**：`createToken` 带过期时间与 abilities；改密/重置后 `tokens()->delete()`；小程序登录显式 `event(new Login(...))`。

### USER-06 ｜ 高 ｜ 授权

**租户资源 `canAccess()` 覆盖策略，且 `UserPolicy` 平台标记与面板错配**

- **位置**：`app/Filament/Tenant/Clusters/User/Resources/{Users,UserRealnames,Identities}/*Resource.php:34-38`、`app/Policies/User/UserPolicy.php:24-28,78-82`
- **失败场景**：无对应权限的租户员工仍可进入「用户/实名/身份」列表（页面准入只走 `canAccess()`）；反过来租户管理员打开用户详情必 403、身份调整按钮永不显示。
- **修复建议**：`canAccess()` 改为 `parent::canAccess() && 模块开关`；策略平台统一为 `Both` 并按租户角色授权。

---

## 四、中优先级问题

### USER-07 ｜ 中 ｜ 隐私

**实名接口下发身份证密文与正反面签名链接**

- **位置**：`app/Http/Resources/User/RealnameResource.php:22-25`、`UserRealname.php:66-95`
- **失败场景**：密文随响应落日志/抓包，配合 CBC 无 MAC 与密钥泄露可还原身份证；照片链接超出「状态查询」最小必要。
- **修复建议**：只返回掩码/`has_id_card`；照片按权限 + 审计单独出短 TTL 链接。

### USER-08 ｜ 中 ｜ 越权 / fail-open

**`AuthorizesModelAccess` 在关联用户为空时放行**

- **位置**：`app/Http/Controllers/Traits/AuthorizesModelAccess.php:22`
- **失败场景**：`user_id` 指向已删除用户或漏用 trait 时，任意登录用户可读改删无主地址、发票、抬头与 PDF。
- **修复建议**：改为「有 user 且等于当前用户才放行」，其余 404/403。

### USER-09 ｜ 中 ｜ 一致性

**身份授予静默替换同租户旧身份，delete + attach 无事务**

- **位置**：`app/Services/User/IdentityService.php:296-306,151-156`
- **失败场景**：支付成功即删除用户已付费的 VIP-A 换成 VIP-B，无退款/确认；delete 后 attach 失败会让用户彻底丢失身份。
- **修复建议**：整个 `entry` 包事务；替换前校验 `is_unique` 语义；补失败用例。

### USER-10 ｜ 中 ｜ 编号

**身份编号 `max('serial')` 取字符串列，编号重复/回退**

- **位置**：`app/Models/User/UserIdentity.php:33-39`、`create_identities_table.php:79-85`
- **失败场景**：字典序比较（`'9' > '10'`）导致新编号回到 10 与历史重复；并发更易出现。
- **修复建议**：`serial` 改整型或 `max(CAST(...))`，加 `unique(identity_id, serial)` 并冲突重试。

### USER-11 ｜ 中 ｜ 半初始化数据

**`User::created` 吞异常产生无账户/无资料用户**

- **位置**：`app/Models/User/User.php:49-58`、`AccountController.php:20-27`
- **失败场景**：注册成功但账户创建失败，之后 `/api/user/account` 对 null 取属性 500；`catch (Exception)` 兜不住 `TypeError`。
- **修复建议**：注册整体事务；`AccountController` 判空并告警。

### USER-12 ｜ 中 ｜ 业务 / 越权

**推荐关系绑定无业务约束、无唯一索引、异常消息直出**

- **位置**：`app/Http/Controllers/User/UserRelationController.php:51-60`、`app/Services/User/UserRelationService.php:70-74`、`create_users_table.php:115-138`
- **失败场景**：任意用户可抢绑头部推广员劫持佣金、枚举用户；并发可插入重复关系；DB 异常直接透给客户端。
- **修复建议**：`unique(user_id)`；限制新用户时间窗/推广员资格/黑名单；异常 `report()` 后返回固定文案。

### USER-13 ｜ 中 ｜ 令牌

**租户令牌 base64 编码后无消费方，唯一解码中间件未注册**

- **位置**：`app/Http/Controllers/Auth/LoginController.php:86-95`、`app/Http/Middleware/AccessTokenAuthenticate.php:25-37`
- **失败场景**：按 `Authorization: Bearer <access_token>` 使用返回的 base64 串必然 401；自定义 query 方式又会把 token 写进访问日志。
- **修复建议**：直接返回原始 `plainTextToken`，或正式注册中间件并文档化。

---

## 五、低优先级问题

### USER-14 ｜ 低 ｜ 异常脱敏

**实名提交与绑定上级 `catch (Throwable)` 后直出内部错误**

- **位置**：`app/Http/Controllers/User/RealnameController.php:84-86`、`UserRelationController.php:58-60`
- **失败场景**：唯一键冲突/字段超长时返回 `SQLSTATE[...]`、表名、键名，绕过统一异常脱敏。
- **修复建议**：只捕业务异常，其余 `report()` 后统一 500 文案。

---

## 六、旧问题状态（2026-11-01 → 2026-11-14）

| 旧编号 | 状态 | 说明 |
|---|---|---|
| USER-01 固定验证码 | 仍存在 | 配置未改 |
| USER-02 短信字段错配 | 仍存在 | 校验 `mobile`、读取 `phone` |
| USER-03 小程序劫持 | 仍存在（新增 Socialite 缺口） | 无 openid 绑定，微信支付/提现不可用 |
| USER-04 支付密码覆盖 | 仍存在 | 无「已设置则拒绝」 |
| USER-05 令牌生命周期 | 仍存在 | 无过期、改密不吊销 |
| USER-06 私有文件归属 | **已修复** | 实名照片改上传 UUID + 归属/磁盘校验，有他人文件/公开盘用例 |
| USER-07 PII 下发 | 仍存在 | 密文 + 签名链接 |
| USER-08 fail-open | 仍存在 | `AuthorizesModelAccess` |
| USER-09 身份静默替换 | 部分修复 | purchase 校验 `is_unique`、有日志；delete+attach 仍无事务 |
| USER-10 serial 重复 | 仍存在 | 字符串 `max` 字典序 |
| USER-11 半初始化用户 | 仍存在 | catch + report |
| USER-12 推荐关系 | 部分修复 | 增加「已存在」校验；无唯一索引/资格约束，异常仍直出 |
| USER-13 发票 order_ids | 仍存在 | 见 Finance FIN-13 |
| USER-14 验证码校验 | 部分修复 | 发送按渠道删除旧码；校验仍不区分渠道/无次数/非原子 |
| USER-15 找回密码 | 仍存在 | 无 forgot/reset |
| USER-16 身份目录跨租户 | 仍存在 | 无租户归属校验 |

---

## 七、修复优先级

1. **P0**：USER-01（固定验证码）、USER-02（短信不可用）。
2. **P1**：USER-03（账号劫持）、USER-04（支付密码）、USER-05（令牌）、USER-06（租户权限绕过）。
3. **P2**：USER-07/08/09/12（PII、越权、身份替换）。
4. **P3**：USER-10/11/13/14 与找回密码。
