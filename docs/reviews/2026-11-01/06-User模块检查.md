# User 模块 检查报告

- **检查日期**：2026-11-01
- **检查范围**：`app/Models/User/*`、`app/Services/User/*`、`app/Http/Controllers/{Auth,User}/*`、`app/Http/Requests/{Auth,User}/*`、`app/Http/Resources/User/*`、`app/Rules/*`、`app/Policies/User/*`、`app/Filament/{Backend,Tenant}/Clusters/User/**`、`routes/apis/{auth,user}.php`、用户/认证相关迁移
- **检查方式**：逐文件静态走读 + 关键链路推演 + 与现有测试比对
- **结论**：用户资料/地址/发票/实名/身份/推荐关系功能齐全，但**认证与会话链路是本期最薄弱的部分**：短信通道字段错配、调试验证码固定 0000、小程序登录可被抢注用户名劫持、支付密码可无旧密码覆盖、令牌永不过期且改密不吊销。另有 `AuthorizesModelAccess` 的 fail-open 越权与半初始化用户问题。

**统计：严重 1 / 高 5 / 中 9 / 低 1（共 16 条）。**

---

## 一、模块概览

| 子域 | 现状 | 主要风险 |
|---|---|---|
| 认证（Auth） | 用户名密码登录（图形验证码 + 双层限流）可用；短信登录、小程序手机号登录、租户签名令牌三条链路均不完整 | 固定 OTP、账号劫持、令牌不过期、无找回密码 |
| 用户（User） | 资料/地址/发票/实名/身份/推荐关系齐全，但多处「先查后写」无事务，IDOR 收口在 trait 且 fail-open | 私有材料越权、身份被静默覆盖、半初始化用户 |
| 后台（User 集群） | 用户/实名/身份/员工/租户/令牌/关系资源齐全 | 租户面板 `canAccess()` 绕过策略（详见 System 报告 SYS-02） |
| 死代码 | `IdentityService::purchase/checkAndAssign`、`SmsService::verifyCode`、`AccessTokenAuthenticate` 无调用方 | 安全/业务能力「写了但没接上」 |

---

## 二、严重问题

### USER-01 ｜ 严重 ｜ 认证 / 弱随机性
**短信验证码默认开启调试模式，验证码固定为 `0000`**

- **位置**：`config/easy-sms.php:11`、`:34-45`、`app/Services/Foundation/SmsService.php:82-90`
- **代码**：
  ```php
  'debug' => env('EASY_SMS_DEBUG', true),          // 默认 true
  'default' => ['gateways' => ['debug', 'aliyun']], // debug 排第一
  'gateways' => ['debug' => ['code' => '0000']],
  ```
  ```php
  private function generateCode(): string
  {
      if (config('easy-sms.debug')) {
          return config('easy-sms.gateways.debug.code');   // 固定 0000
      }
      ...
  }
  ```
- **失败场景**：`.env.example` 未声明 `EASY_SMS_DEBUG`，任何按示例部署的环境都会走调试网关：`generateCode()` 恒返回 `0000`，`verifyCode()` 用明文相等比较。一旦短信登录/找回密码/改绑手机号接入，攻击者输入任意手机号 + `0000` 即可登录任意账号。即使不接入，验证码本身也已失去随机性。
- **建议**：默认改为 `false`，从默认网关列表移除 `debug`（仅 local/testing 通过环境变量显式加入）；非测试环境强制 `random_int`；对固定码加启动断言。

---

## 三、高优先级问题

### USER-02 ｜ 高 ｜ 功能缺陷
**短信发送接口字段名错配（校验 `mobile`、读取 `phone`），线上短信通道实际不可用**

- **位置**：`app/Http/Requests/Auth/SmsSendRequest.php:18-21`、`app/Http/Controllers/Auth/SmsController.php:23-25`
- **代码**：
  ```php
  // Request 只校验 mobile
  'mobile' => ['required', new MobileRule],
  ```
  ```php
  // Controller 读取 phone
  $phone = $request->safe()->string('phone');
  if (service(SmsService::class)->sendCode($phone, SmsChannel::Login)) {
  ```
- **失败场景**：`safe()` 只包含通过校验的键（`mobile`、`captcha_*`），不存在 `phone`；`string('phone')` 返回空 `Stringable`，被转成 `''` 传给 `sendCode()`。于是先删除 `phone=''` 的旧记录，再以空号码调网关，异常被 `catch` 后返回「短信验证码发送失败」——用户永远收不到验证码，且每次调用都会清空同渠道历史验证码。现有 `RateLimitTest` 只断言 429，未覆盖成功路径。
- **建议**：统一字段为 `mobile`（或同时接受 `phone` 并做 `requiredIf`）；补「发送成功后 `sms_codes` 落库」测试。

### USER-03 ｜ 高 ｜ 认证 / 越权
**小程序手机号登录可被「手机号用户名」抢注劫持，且取任意租户的小程序配置**

- **位置**：`app/Http/Controllers/Auth/MiniProgramController.php:28-31`、`:45-57`、`app/Http/Requests/Auth/RegisterRequest.php:16-18`
- **代码**：
  ```php
  $wechatMini = WechatMini::first();          // 无租户条件
  ...
  $user = User::firstOrCreate(
      ['username' => $phoneNumber],
      ['password' => null]
  );
  ```
  ```php
  // 注册允许 3-32 位任意字符串，未禁止纯手机号
  'username' => 'required|string|min:3|max:32|unique:users,username',
  ```
- **失败场景**：
  1. 攻击者先注册 `username = 受害者手机号` 并设置密码；受害者用微信手机号快捷登录时，`firstOrCreate` 命中攻击者账号，受害者的下单、地址、实名材料全部落在攻击者可控账号里，攻击者用密码随时登录。
  2. 登录未校验 `openid/unionid`、未写 `Socialite/SocialiteAccount`，任何人只要持有一一次性 `code` 就能换取该手机号会话。
  3. `WechatMini::first()` 无租户上下文，多租户下会用 A 租户的 `app_id/app_secret` 校验 B 租户用户的 code。
- **建议**：注册禁止纯手机号格式（或改为独立 `phone` 列 + 唯一索引）；小程序登录以 `SocialiteAccount(provider, openid)` 为唯一键，手机号仅作绑定；`WechatMini` 查询带租户；登录成功补发 `Login` 事件。

### USER-04 ｜ 高 ｜ 认证 / 越权
**支付密码「设置」接口可在不知道原密码的情况下覆盖**

- **位置**：`app/Http/Controllers/User/SafeController.php:78-89`、`app/Http/Requests/User/SetPaymentPasswordRequest.php:25-27`、`app/Services/Finance/UserAccountService.php:238-243`
- **代码**：
  ```php
  // 只有 change 路由才要求旧密码
  if ($this->routeIs('user.safe.payment-password.change')) {
      $rules['old_password'] = ['required', new PaymentPasswordRule];
  }
  ```
  ```php
  public function setPaymentPassword(UserAccount $account, string $password): bool
  {
      $account->update(['payment_password' => $password]);   // 不检查是否已存在
  }
  ```
- **失败场景**：`POST /api/user/safe/payment-password` 与 `PUT ...` 是同前缀两条路由，前者不校验旧密码。攻击者拿到任意有效会话后直接 `POST` 即可把支付密码改成自己的，随后用于提现/免密支付确认；受害者无感，也没有通知或审计。
- **建议**：`setPaymentPassword()` 内判断已存在则拒绝（提示走修改接口）；为变更写 `EventLog` 并发通知。

### USER-05 ｜ 高 ｜ 会话生命周期
**令牌永不过期、改密不吊销、小程序登录不写登录记录**

- **位置**：`app/Http/Responses/AuthResponse.php:32-34`、`app/Http/Controllers/User/SafeController.php:23-29`、`MiniProgramController.php:57`
- **代码**：
  ```php
  'token' => $user->createToken($this->tokenName)->plainTextToken,   // 无 expiresAt / abilities
  ```
  ```php
  Auth::user()->update(['password' => ...]);   // 不删除 tokens
  ```
- **失败场景**：① 登录一次即获永久全权限令牌，每次登录都新发一枚，旧令牌继续有效；② 改密后被盗令牌仍可用；③ 小程序登录未触发 `Login` 事件，`AuthenticatedRecord` 不执行，`/user/safe/records` 看不到微信登录，异常登录无法察觉。
- **建议**：`createToken('API', ['user'], now()->addDays(30))`；改密/重置后 `tokens()->delete()` 并重签；提供「退出所有设备」；小程序登录显式派发 `Login`。

### USER-08 ｜ 高 ｜ 越权 / fail-open
**`AuthorizesModelAccess::checkPermission()` 在关联用户为空时直接放行**

- **位置**：`app/Http/Controllers/Traits/AuthorizesModelAccess.php:20-31`
- **代码**：
  ```php
  if (!$model->user || $model->user->is(Auth::user())) {
      return;
  }
  ```
- **失败场景**：`$model->user` 为 null（`user_id` 指向已物理删除用户、脏数据、或模型忘记 `use BelongsToUser`）时直接放行。`AddressController`、`InvoiceController`、`InvoiceTitleController`、`Mall\OrderController` 等都走这个 trait，任何登录用户都能读取/修改/删除这类「无主」记录（含收货地址、发票抬头、发票 PDF）。与基础设施报告 INF-05 同源。
- **建议**：改为「有 user 且等于当前用户才放行」，其余一律 404/403；长期改用 Policy。

### USER-06 ｜ 中（按利用条件可升高） ｜ 越权 / 隐私
**私有文件校验不校验归属，实名材料路径可被引用**

- **位置**：`app/Rules/FileExistsRule.php:43-48`、`app/Http/Requests/User/StoreUserRealnameRequest.php:31-32`
- **代码**：
  ```php
  $disk = Storage::disk($this->visibility->disk());
  $path = $this->extractStoragePath($value);
  if (!$disk->exists($path)) { $fail(...); }
  ```
- **失败场景**：规则只判断「文件在私有盘存在」，不校验上传者。攻击者拿到他人私有文件路径（历史临时链接、日志、客服截图）后，可在提交实名时把 `id_card_front` 指向该路径，随后通过 `GET /api/user/realname` 拿到该文件的临时签名 URL，读取他人身份证照片（详见 USER-07）。
- **建议**：上传时记录 `user_id`（独立 `uploads` 表或元数据），`FileExistsRule` 校验归属；或改为一次性 file id。

### USER-07 ｜ 中 ｜ 隐私 / 数据暴露
**实名接口把身份证密文与正反面照片签名链接一起下发**

- **位置**：`app/Http/Resources/User/RealnameResource.php:22-25`、`app/Http/Controllers/User/RealnameController.php:34-42`、`app/Models/User/UserRealname.php:36-58`
- **代码**：
  ```php
  'name' => $this->resource->name,
  'id_card_number' => $this->resource->id_card_number,   // 密文
  'id_card_front' => $this->resource->id_card_front_url,
  'id_card_back' => $this->resource->id_card_back_url,
  ```
- **失败场景**：`id_card_number` 读取返回密文，Resource 原样下发给客户端；密文一旦被抓包/落日志（`ApiRecord` 若启用会记录响应体），配合泄露的 `AES_KEY` 或 CBC 填充预言（FND-01）即可还原身份证号。同时一次性下发正反面照片签名链接，超出「状态查询」最小必要。
- **建议**：列表/详情只返回掩码或 `has_id_card: true`；明文仅在后台审核页按权限 + 审计查看；照片链接改为按需、短 TTL、单独鉴权接口。

### USER-09 ｜ 中 ｜ 数据一致性 / 业务
**身份授予会静默删除同租户旧身份，`is_unique` 语义未实现**

- **位置**：`app/Services/User/IdentityService.php:288-297`、`app/Models/User/Identity.php:32-34`、`create_identities_table.php:85`
- **代码**：
  ```php
  if ($existingPivot) {
      DB::table('user_identity')
          ->where('user_id', $user->getKey())
          ->where('tenant_id', $tenantId)
          ->delete();
  }
  $user->identities()->attach($identity->getKey(), $data);
  ```
- **失败场景**：`user_identity` 有 `unique(user_id, tenant_id)`，同租户只能有一个身份。用户已持有 VIP-A 再购买 VIP-B，支付成功后会**直接删除 VIP-A**，不写移除事件、不退款、不校验 `is_unique`（只有全项目无调用方的 `purchase()` 查过一次）。等于用户花钱买了个「降级」。
- **建议**：明确产品语义——唯一身份则支付前校验并拒绝；多身份则去掉唯一索引并改写 `entry`。删除旧身份必须走统一 `remove()`（事件/日志/退款）。

### USER-10 ｜ 中 ｜ 并发
**身份编号 `max+1` 并发重复，且无唯一约束**

- **位置**：`app/Models/User/UserIdentity.php:32-37`、`create_identities_table.php:79-85`
- **代码**：
  ```php
  $current = self::where('identity_id', $identity->getKey())->max('serial');
  return max($reserve, $current) + 1;
  ```
- **失败场景**：两个用户同时支付同一身份，都读到 `max=100`，都写入 `serial=101`；`serial` 只有普通列、无唯一索引，不会报错。对外展示的会员编号重复，后续按编号发奖/核销会命中多人。`entry()` 的 `attach` 也未包事务。
- **建议**：`unique(['identity_id','serial'])` + 冲突重试；或数据库序列/Redis `INCR`；`entry()` 用事务包裹删除、attach、日志、事件。

### USER-11 ｜ 中 ｜ 数据一致性
**`User::created` 吞异常，产生无账户/无资料的半初始化用户**

- **位置**：`app/Models/User/User.php:49-58`、`app/Http/Controllers/User/AccountController.php:20-24`
- **代码**：
  ```php
  self::created(static function (User $user) {
      try {
          $user->profile()->create([...]);
          $user->account()->create();
      } catch (Exception $e) {
          report($e);
      }
  });
  ```
- **失败场景**：注册时若 `user_accounts` 插入失败（唯一键、字段超长、DB 抖动），用户仍创建成功并返回 201，但永远没有账户；之后 `GET /api/user/account` 对 `null` 取属性 → 500。`catch` 只捕 `Exception`，`TypeError` 不会被兜住。
- **建议**：注册流程放 `DB::transaction()`，失败整体回滚；`AccountController` 增加 `abort_if(!$account, 500)` 并告警。

### USER-12 ｜ 中 ｜ 业务 / 越权
**推荐关系可绑定任意上级、无唯一约束、异常消息直出**

- **位置**：`app/Http/Controllers/User/UserRelationController.php:50-58`、`create_users_table.php:115-132`、`UserRelation.php:166-169`
- **代码**：
  ```php
  public function bind(int $parentId): JsonResponse
  {
      try {
          $this->userRelationService->createRelation($user, $parentId);
      } catch (\Throwable $exception) {
          return ApiResponse::error($exception->getMessage());
  ```
- **失败场景**：① `bind/{parentId}` 无业务约束（不限推广员、不校验黑名单、无需确认、不限注册时长），任意用户可「抢绑」到头部推广员名下劫持佣金归属，也可枚举用户是否存在；② `user_relations.user_id` 只有普通索引、无唯一键，并发 `bind` 可插入重复关系，层级/团队人数错乱；③ 异常消息原样返回，DB 错误（表名、SQLSTATE）直接暴露。
- **建议**：增加「仅新用户 N 天内可绑 + 上级必须是有效推广员 + 黑名单校验 + 需确认」；迁移补 `unique(user_id)`；异常统一 `report()` 后返回固定文案，仅业务异常透出。

### USER-13 ｜ 中 ｜ 校验 / 健壮性（与 Finance 交叉）
**发票申请不带 `order_ids` 必然 500**

- **位置**：`app/Http/Controllers/User/InvoiceController.php:102-109`、`InvoiceApplicationRequest.php:17-21`、`InvoiceService.php:46`
- **代码**：
  ```php
  $orderIds = $request->input('order_ids', []);
  $tenantId = Order::whereIn('id', $orderIds)->value('tenant_id');   // null
  $application = $service->createApplication($user->id, $tenantId, ...);   // int 形参
  ```
- **失败场景**：`order_ids` 为 `nullable`，不传时 `value()` 返回 null，传给 `createApplication(int $tenantId)` → `TypeError`；即使绕过，`$data['amount']` 也从未赋值。用户在有抬头、无订单（或「按金额开票」）时无法开票。
- **建议**：`order_ids` 改为 `required|array|min:1`，或补齐「按金额开票」的 `amount` 校验与租户推导；详见 Finance FIN-13。

### USER-14 ｜ 中 ｜ 认证 / 暴力破解
**验证码校验不区分渠道、无失败计数、非原子**

- **位置**：`app/Services/Foundation/SmsService.php:99-112`
- **代码**：
  ```php
  $sms = SmsCode::where('phone', $phone)
      ->where('expires_at', '>', Carbon::now())
      ->first();
  if ($sms && !$sms->used && $sms->code === $code) {
      $sms->update(['used' => true]);
  ```
- **失败场景**：① 查询不带 `channel`，注册渠道的验证码可用于登录（跨场景复用）；② 无 `attempts` 计数/锁定，4 位码在 5 分钟窗口内可无限次尝试；③ `sendCode()` 先 `delete` 再发送，网关失败时用户原有有效码被清空（可用性 DoS）；④ 校验与置位分两步，无原子性，同一码可并发复用两次。
- **建议**：查询加 `channel`；`sms_codes` 加 `attempts`，失败 N 次作废；校验用条件更新 `where('used', false)->update(['used' => true])` 并判断影响行数；发送成功后再替换旧码。

### USER-15 ｜ 中 ｜ 设计缺失
**忘记/重置密码链路完全缺失，`password_reset_tokens` 表也不存在**

- **位置**：`config/auth.php:103-109`、`routes/apis/auth.php`、`app/Enums/User/SmsChannel.php`
- **失败场景**：配置引用了 `password_reset_tokens`，但迁移中没有该表；路由只有 captcha/sms/password/tenant/mini/register，无 `forgot`/`reset`；`SmsChannel::Forgot`、`SmsService::verifyCode()` 全部无调用方。用户忘记密码只能人工介入。
- **建议**：补迁移与 `POST /auth/password/forgot|reset`，强制「验证码带渠道 + 次数限制 + 重置后吊销全部令牌」。

### USER-16 ｜ 低 ｜ 跨租户信息泄露
**任意登录用户可枚举其他租户的身份目录**

- **位置**：`app/Http/Controllers/User/IdentityController.php:38-47`
- **代码**：
  ```php
  public function available(int $tenantId): JsonResponse
  {
      $identities = Identity::where('tenant_id', $tenantId)->where('can_subscribe', true)->where('status', true)
  ```
- **失败场景**：`$tenantId` 来自 URL，不校验当前用户是否属于该租户，也不校验租户状态/过期。任意登录用户可遍历 `tenantId=1..N`，拿到所有租户的身份名称、价格、权益规则、升级条件；`check(Identity $identity)` 同样不校验归属。
- **建议**：仅允许查询当前 `X-Tenant-Id` 对应租户（并与 `user_tenant` 授权校验），或只返回用户所属租户的身份；`check` 增加 `whereBelongsTo`。

---

## 四、后台（User 集群）观察

- 资源齐全：用户列表/详情、实名审核、身份调整、员工管理、租户管理、令牌、推荐关系。
- **租户面板 3 个资源覆写 `canAccess()` 绕过 `viewAny` 策略**（`Users` / `UserRealnames` / `Identities`），任意租户员工可浏览用户与身份证 PII —— 详见 System 报告 SYS-02。
- 「获取令牌」Action 打开弹窗即签发永久 Token —— 详见基础设施 INF-07。
- 「员工登录」Action 把用户名放 query、密码为空 —— 详见基础设施 INF-25。

---

## 五、修复优先级

1. **P0（当天）**：USER-01（固定验证码）、USER-02（短信不可用）。
2. **P1（本周）**：USER-03（账号劫持）、USER-04（支付密码覆盖）、USER-05（令牌生命周期）、USER-08（fail-open）。
3. **P2（本迭代）**：USER-06/07（私密材料与 PII）、USER-09/10（身份覆盖/编号）、USER-11（半初始化用户）、USER-12/13（推荐/发票）。
4. **P3**：USER-14/15（验证码加固、找回密码）、USER-16（身份目录）。
