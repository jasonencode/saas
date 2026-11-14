# Backend（运营端）面板检查报告

- **检查日期**：2026-11-01
- **检查范围**：`app/Filament/Backend/**`（7 个 Cluster、Pages、Widgets）、`app/Filament/Actions/**`、`app/Policies/**`、运营端使用的模型/迁移
- **检查方式**：逐文件静态走读 + Filament 5 授权/批量动作源码交叉核对 + 关键模型字段核对
- **前提**：面板全局 `->strictAuthorization(false)`（`FilamentPanelProvider.php:76`），**策略或方法缺失时默认放行**，本报告多处问题因此被放大。

**统计：严重 5 / 高 8 / 中 13 / 低 4（共 30 条）。**

---

## 一、运营端盘点

| Cluster | Resource 数 | 主要资源 | 备注 |
|---|---|---|---|
| Mall | 16 | Orders、Refunds、Products、Categories、Deliveries、Topics、Banners、Suppliers、Tags、Regions、Expresses、PickupPoints、ReturnAddresses、Configures、Addresses、Applies | 订单/退款为只读 |
| Content | 9 | Contents、SinglePages、Categories、Tags、Comments、Suggests、Sensitives、AppVersions、Notifications | AppVersions 平台级（无 tenant） |
| Campaign | 3 | Coupons、Lotteries、Redpacks | 含红包码关系管理器 |
| Finance | 10 | Accounts、Payments、RechargeOrders、Refunds、WithdrawOrders、Invoices、InvoiceApplications、InvoiceTitles、Vouchers、Plans | 资金核心，危险动作最多 |
| Foundation | 7 | Aliyuns、Wechats、WechatPayments、WechatMinis、Alipays、Socialites、SocialiteAccounts | 平台级密钥与云资源 |
| User | 7 | Users、Tenants、Staffers、Identities、Realnames、Tokens、UserRelations | 面板准入与 PII 集中区 |
| Setting | 12 | Administrators、Roles、Systems、BlackLists、ApiLogs、DbLogs、EventLogs、Exports、Imports、JobBatches、FailedJobs、ScheduleRunLogs（+ Horizon 页面） | 另一份报告已覆盖 Setting/User 的通用问题 |

Widgets：`AccountWidget`、`SystemInfoWidget`、`TenantOverview`、`UserOverview`、`ContentOverview`、`OrderOverview`、`FinanceOverview`，各 Cluster 另有 `*StatsWidget`。

---

## 二、严重问题

### BE-01 ｜ 严重 ｜ 运行时报错
**订单详情页必崩：`OrderInfolist` 使用了未导入的 `Order` 类**

- **位置**：`app/Filament/Backend/Clusters/Mall/Resources/Orders/Schemas/OrderInfolist.php:10-16`、`:79`
- **代码**：
  ```php
  use App\Enums\Mall\FulfillmentType;
  use Deldius\UserField\UserEntry;
  use Filament\Infolists;
  // ...没有 use App\Models\Mall\Order;
  ```
  ```php
  ->visible(fn (Order $record): bool => bccomp((string) $record->coupon_discount, '0', 2) === 1),
  ```
- **失败场景**：命名空间是 `...\Orders\Schemas`，`Order` 被解析为 `App\Filament\Backend\Clusters\Mall\Resources\Orders\Schemas\Order`（不存在）。Filament 渲染时传入 `App\Models\Mall\Order` 实例 → `TypeError`，**任何运营人员打开订单详情都会 500**，且该区块是「金额信息」核心内容。
- **建议**：补 `use App\Models\Mall\Order;`；同时核查同目录其他闭包的模型类型（`ProductInfolist`、`IdentityInfolist`、`UserInfolist` 均已正确导入，说明是漏写）。

### BE-02 ｜ 严重 ｜ 功能缺失
**运营端订单全流程不可操作**

- **位置**：`Backend/Clusters/Mall/Resources/Orders/Tables/OrdersTable.php:56-79`、`Pages/ViewOrder.php:21-28`
- **代码**：
  ```php
  // OrdersTable 只有 columns + filters，没有 recordActions / toolbarActions
  ->filters([TenantFilter::make(), DateRangeFilter::make('created_at'), ...]);
  ```
  ```php
  // ViewOrder 头部动作只有返回
  return [BackAction::make()];
  ```
- **失败场景**：租户端订单表挂了核销/发货/签收/取消等 Action（`Tenant/.../OrdersTable.php:119-130`），`OrderPolicy` 的平台标记是 `Both`，这些 Action 在运营端全部可用；但运营端没有入口，`OrderShipAction`、`OrderCancelAction`、`OrderSignAction`、`OrderVerifyAction`、`OrderPaymentAction`、`OrderModifyAddressAction`、`OrderUpdateFreightAction`、`OrderAddRemarkAction` 在后台全为死代码。平台客服接到「用户没收到货/要取消」工单时无法处理。
- **建议**：`OrdersTable` 与 `ViewOrder` 复用租户端的 Action 集合；若定位就是只读，则从策略层移除这些按钮权限，避免权限树授予永远用不到的按钮。

### BE-03 ｜ 严重 ｜ 授权
**面板准入用「是否属于任何租户」判定，导致越权与误锁**

- **位置**：`app/Models/System/Administrator.php:86-93`
- **代码**：
  ```php
  public function canAccessPanel(Panel $panel): bool
  {
      if ($panel->getId() === 'tenant') {
          return $this->tenants()->count();
      }
      return !$this->tenants()->count();   // 运营端准入 = 不属于任何租户
  }
  ```
- **失败场景**：
  - **越权**：租户员工被 `StaffersRelationManager` 的 `DetachAction` 解除全部租户后，`tenants()->count() === 0` → **自动获得运营端登录资格**；其角色权限大量为 `PolicyPlatform::Both`，可直接读取全平台数据。
  - **误锁**：运营管理员被加入任意租户后，立刻被判为不能进运营端；若是唯一超管，后台直接不可用。
- **建议**：按 `type` 判定（`Admin` → backend，`Tenant` → tenant 且必须有租户）；`StaffersRelationManager` 的 Attach 选择器限制 `type = Tenant`。

### BE-04 ｜ 严重 ｜ 安全
**「获取令牌」打开弹窗即签发永久 API Token**

- **位置**：`app/Filament/Actions/User/GenerateTokenAction.php:26-36`、`config/sanctum.php`（`expiration = null`）
- **代码**：
  ```php
  $this->fillForm(fn (User $record) => ['token' => $record->createToken('T:0')->plainTextToken]);
  ```
- **失败场景**：`fillForm` 在**弹窗挂载时**执行（不是提交），每点一次就在 Sanctum 新建一个**永久有效**（`expiration=null`）、全权限、无 IP 绑定、无审计的令牌，明文展示，关闭弹窗也不回收。等于管理员可静默冒充任意用户调用全部 API（含资金、地址、订单），事后无法追溯签发人。
- **建议**：改为「二次确认 + 一次性展示 + 短 TTL + 审计」：`requiresConfirmation()`、`createToken('backend:'.Filament::auth()->id(), ['*'], now()->addMinutes(10))`；提供「吊销该用户全部令牌」。

### BE-05 ｜ 严重 ｜ 隐私（PII）
**实名认证身份证号明文、可复制，KYC 材料可物理删除**

- **位置**：`Backend/Clusters/User/Resources/Realnames/Tables/RealnamesTable.php:26-32`、`:65-71`、`Schemas/RealnameInfolist.php`
- **代码**：
  ```php
  Tables\Columns\TextColumn::make('id_card_number')
      ->getStateUsing(fn (UserRealname $record): ?string => $record->decryptedIdCardNumber())
      ->copyable(),
  ...
  Actions\DeleteBulkAction::make(),
  Actions\ForceDeleteBulkAction::make(),
  ```
- **失败场景**：列表一次性把全平台真实姓名 + 完整身份证号渲染到浏览器，`copyable()` 让批量导出极易实现；`searchable()` 可按姓名枚举。任何拥有「实名列表」权限的人都能拖选复制数十万条身份证号。`ForceDeleteBulkAction` 可物理清除 KYC 留痕（合规通常要求保存 3 年以上）。
- **建议**：列表掩码（`****1234`）并去掉 `copyable`；完整值/影像仅在详情页，增加独立「查看证件」权限 + 每次查看写审计；移除强制删除，改为逻辑失效。

---

## 三、高优先级问题

### BE-06 ｜ 高 ｜ 数据安全
**批量审核绕过 `ProductService`，可把已下架/已驳回商品强制上架**

- **位置**：`app/Filament/Actions/Mall/ProductBulkAuditAction.php:37-43`、`app/Services/Mall/ProductService.php:37-39`
- **代码**：
  ```php
  $records->each(fn ($record) => $record->update(['status' => ProductStatus::Up]));
  ```
- **失败场景**：单条 `ProductAuditAction` 走 `ProductService::audit()`（要求 `status === Pending`），批量版直接 `update()`：勾选已下架/已驳回/已上架商品点「批量审核」，全部变成上架，无审核留痕、无事务、无逐条权限校验。误操作一次即可把违规下架商品重新对外销售。
- **建议**：逐条调用 `ProductService::audit()` 并过滤 `Pending`，失败条目汇总提示；整批包事务。

### BE-07 ｜ 高 ｜ 数据安全
**提现单 / 支付退款单可被无条件批量删除（含已打款）**

- **位置**：`Finance/Resources/WithdrawOrders/Tables/WithdrawOrdersTable.php:70-76`、`Finance/Resources/Refunds/Tables/RefundsTable.php:86-89`、`WithdrawOrderPolicy.php:41-51`、`PaymentRefundPolicy.php:41-51`
- **代码**：
  ```php
  Actions\DeleteBulkAction::make(),
  Actions\ForceDeleteBulkAction::make(),
  ```
  ```php
  public function deleteAny(Authenticatable $user): bool
  {
      return $user->hasPermission(__CLASS__, __FUNCTION__);   // 无状态约束
  }
  ```
- **失败场景**：资金流水凭证可被批量删除/永久删除，`Pending/Paid/Completed` 的提现单、`Processing/Completed` 的退款单都会消失；对账时「钱已出、单已无」，且无单据可追溯。
- **建议**：`delete/deleteAny` 仅允许 `Rejected/Cancelled/Failed`；移除强制删除（或超管 + 二次密码 + 审计）；长期资金表禁止 UI 删除。

### BE-08 ｜ 高 ｜ 数据安全
**红包码金额/状态可直接编辑，已领取码可删除**

- **位置**：`Campaign/Resources/Redpacks/RelationManagers/CodesRelationManager.php:28-43`、`:82-87`
- **代码**：
  ```php
  Forms\Components\TextInput::make('amount')->label('金额')->required()->numeric(),
  Forms\Components\Select::make('status')->options(RedpackCodeStatus::class)->required(),
  ...
  Actions\EditAction::make(), Actions\DeleteAction::make(),
  ```
- **失败场景**：红包码是已发行资金凭证。可把 `claimed` 码的 `amount` 从 5 改成 500，或把 `disabled` 改回 `unused` 让用户重复领取；删除已领取记录会让活动统计与支出账不符。全程绕过 `RedpackService`，且没有 `RedpackCodePolicy`。与 CAMP-05 同源。
- **建议**：关系管理器只读；冲正走带权限与备注的「作废/调账」Action；`amount/status` 加 `disabled()` 并补策略。

### BE-09 ｜ 高 ｜ 安全
**云厂商/微信密钥在表单中明文回显**

- **位置**：`Foundation/Resources/Aliyuns/Schemas/AliyunForm.php:20-26`、`Foundation/Resources/Wechats/Schemas/WechatForm.php:27-32`、`WechatPayments/Schemas/WechatPaymentForm.php`
- **代码**：
  ```php
  Forms\Components\TextInput::make('app_secret')->label('Access Key Secret')->required(),
  ```
- **失败场景**：编辑页把真实 Secret 原样回填并显示在 DOM、浏览器自动填充、截图/录屏里；拿到凭据即可控制租户/平台的 OSS/CDN/DNS/收款。与 FND-03 同源。
- **建议**：`->password()->revealable(false)->dehydrated(fn ($state) => filled($state))`，placeholder「已配置，留空不修改」；数据库 `encrypted` cast；查看明文单独权限 + 审计。

### BE-10 ｜ 高 ｜ 授权 / 稳定性
**DNS 关系管理器直接改生产解析且零鉴权；ECS 表每次渲染直连阿里云**

- **位置**：`Foundation/Resources/Aliyuns/RelationManagers/DnsRelationManager.php:107-139`、`:192-201`、`EcsRelationManager.php:25-53`
- **失败场景**：`AliyunDns` 不是持久化模型，Filament 不会走策略；文件内无任何 `authorize()/visible()`，只有「详情」权限的运维也能删掉生产域名解析（不可逆）。`EcsRelationManager::getTableRecords()` 每次渲染/翻页/搜索都同步调用 `DescribeInstances`，且 `catch (Exception)` 静默返回空数组——鉴权失败、网络错误、密钥过期全部显示为「没有服务器」。
- **建议**：写动作加 `visible(userCan(...))` 并补策略方法；无权限时 `isReadOnly()`；ECS/DNS 查询加 60s 缓存，区分「空结果」与「调用失败」。

### BE-11 ｜ 高 ｜ 功能缺失 / 死页面
**表格缺 `ViewAction`，详情页与关键动作不可达**

- **位置**：`Finance/Resources/Invoices/Tables/InvoicesTable.php:52-55`、`InvoiceApplications/Tables/InvoiceApplicationsTable.php:51-56`、`Payments/PaymentsTable.php`、`RechargeOrders/RechargeOrdersTable.php`、`Mall/Resources/Applies/Tables/AppliesTable.php:43-48`、`Mall/Resources/Refunds/Tables/RefundsTable.php:46-49`
- **失败场景**：
  - 发票列表每行一个空「操作」下拉，无法进入发票详情（`view` 路由已注册）。
  - `RejectInvoiceApplicationAction` 只挂在 `ViewInvoiceApplication` 头部，但列表没有 `ViewAction` → **运营端无法拒绝任何开票申请**。
  - 支付单、充值单、店铺申请、商城退款都注册了 `view` 页面，列表无入口，只能手输 URL。
- **建议**：统一补 `Actions\ViewAction::make()`；删除空 `ActionGroup`；把 `RejectInvoiceApplicationAction` 也放进行内动作。

### BE-12 ｜ 高 ｜ 安全
**租户登录页从 URL 预填密码，后台「员工登录」把 password 塞进 URL**

- **位置**：`app/Filament/Tenant/Pages/Auth/LoginPage.php:19-28`、`app/Filament/Actions/Tenant/StafferLoginAction.php:25-30`
- **代码**：
  ```php
  // Tenant LoginPage::mount()
  $this->form->fill([
      'username' => request()->input('username'),
      'password' => request()->input('password'),   // 直接采信 URL
      'remember' => true,
  ]);
  ```
  ```php
  // StafferLoginAction
  route('filament.tenant.auth.login', ['username' => $staffer->username, 'password' => ''])
  ```
- **失败场景**：① 登录页无条件采信 `?password=`，可构造 `.../login?username=admin&password=xxx` 的钓鱼链接，预填密码并默认勾选「记住我」；② 「员工登录」按钮名不副实（只是带用户名跳转），却把 `password=` 写进浏览器历史/Referer/反代日志。
- **建议**：登录页只接受 `username`，绝不从 URL 读取 `password`，`remember` 默认关闭；`StafferLoginAction` 改为服务端一次性签名 token 的受控代登录（带审计），或移除。

### BE-13 ｜ 高 ｜ 多租户
**租户成员管理器可把任意平台管理员挂到任意租户**

- **位置**：`Backend/Clusters/User/Resources/Tenants/RelationManagers/StaffersRelationManager.php:119-141`
- **代码**：
  ```php
  Actions\AttachAction::make()
      ->preloadRecordSelect()
      ->recordSelectSearchColumns(['username', 'name'])   // 未限制 type
  ```
- **失败场景**：`administrators` 是全局表，选择器不限制 `type = Tenant`，也不排除已属其它租户的员工。可把 A 租户员工挂到 B 租户（跨租户人员混用，B 数据被 A 员工看到），也可把平台管理员挂进租户，触发 BE-03 的「运营端立即失联」。
- **建议**：`recordSelect` 限制 `type = Tenant` 且 `whereDoesntHave('tenants')`（或明确一人一租户）；补 Attach 的 `authorize`。

---

## 四、中优先级问题

| 编号 | 问题 | 位置 | 说明与建议 |
|---|---|---|---|
| BE-14 | 商城分类表单无租户字段，parent 树跨租户 | `Mall/Resources/Categories/Schemas/CategoryForm.php:24-36` | 其它同类表单都用 `TenantSelect`，这里没有；新建时 `tenant_id = null` 产生「任何租户都看不到的孤儿分类」，`parent_id` 树把所有租户分类混在一起。补 `TenantSelect` + `where('tenant_id', $get('tenant_id'))`。 |
| BE-15 | 内容标签未按租户过滤、切换租户不重置分类 | `Content/Resources/Contents/Schemas/ContentForm.php:58-96` | `tags` 查询无 `tenant_id`，可给 A 租户文章挂 B 租户标签；`createOptionForm` 新建标签也没有 `tenant_id`；`tenant_id` 是 `live()` 但改租户不重置 `category_id`。补租户过滤与 `afterStateUpdated`。 |
| BE-16 | 优惠券可直接发给其它租户的用户 | `Campaign/Resources/Coupons/RelationManagers/UsersRelationManager.php:44-51` | `AttachAction` 默认列全平台用户；`DetachAction` 可删已用券记录。与 CAMP-14 同源。 |
| BE-17 | 单页 `slug` 仅表单层全局唯一，跨租户互相阻塞 | `Content/Resources/SinglePages/Schemas/SinglePageForm.php:35-38` | 表单全局 `unique`，DB 无唯一索引。与 CONT-02 同源，改 `->unique(...->where('tenant_id'))` + 迁移唯一键。 |
| BE-18 | 仪表板 5 个 Stat 组件 30 秒轮询全表聚合 | `Widgets/{Tenant,User,Order,Content,Finance}Overview.php` | 每次轮询约 13-15 条 `count()/sum()`，`whereDate` 不可走索引；`UserOverview` 同一计数算两遍。加 `Cache::remember(60s)`、改 `whereBetween`、轮询降频或关闭。 |
| BE-19 | 统计卡深链参数无效 / 指向错误资源 | `Finance/Widgets/FinanceStatsWidget.php:84-100`、`Mall/Widgets/StatsOverview.php:55-59` | `tab=pending` 在 `ManagePayments/ManageRefunds` 上无 `getTabs()` 被忽略；`pending_refunds_count` 统计 `Mall\Refund` 却链到 Finance 支付退款；`tab=paid` 不是合法 `OrderScope`。补 tab 或去掉参数、修正链接。 |
| BE-20 | 通知列表无空值保护 + N+1 | `Content/Resources/Notifications/Tables/DatabaseNotificationsTable.php:17-20` | 用户被强制删除后 `$record->notifiable->name` 500；未 `with('notifiable')`。加 `?->` 与预加载。 |
| BE-21 | SKU 库存列被当成金额渲染 | `Mall/Resources/Products/RelationManagers/SkusRelationManager.php:29-36` | `stock` 用了 `->money('cny')`（库存 100 显示 `¥100.00`），`origin_price` 反而没格式化。去掉 `money()`、给原价补上；SKU 增删改建议走服务并记日志。 |
| BE-22 | 结算单可改结算对象/执行时间，绕过结算服务 | `Finance/Resources/Vouchers/Tables/VouchersTable.php:60-64`、`Schemas/VoucherForm.php:34-59` | 可换绑 `plan_id/user_id/target_type/target_id/scheduled_at`，直接改资金归属；`getTargetOptions()` 把租户全部订单一次载入下拉，大租户可 OOM。结算单只读或仅允许改时间；目标选择改服务端搜索。 |
| BE-23 | 评论管理只有物理删除，无租户列、无审核动作 | `Content/Resources/Comments/Tables/CommentsTable.php:16-41` | 违规评论只能销毁证据；无 `TenantFilter`/租户列，无法定位；`content` 不截断。加租户列/筛选、`ToggleCommentStatusAction`、`limit(80)`。 |
| BE-24 | 敏感词批量导入不 trim，CRLF 词永不命中 | `app/Filament/Actions/Content/BatchCreateSensitiveAction.php:34-40` | `explode("\n")` 留下行尾 `\r`，整批敏感词失效；空行写入空词条。改 `preg_split('/\R/u') + trim + filter + unique`，`keywords` 加唯一索引。 |
| BE-25 | 角色权限编辑可自授任意权限，且「先删后建」无事务 | `Setting/Resources/Roles/Pages/EditRole.php:26-46`、`Schemas/RoleForm.php:39-50` | 任何有「角色编辑」权限的人可把任意策略（含管理员删除、提现审批、余额调账）勾给自己角色；`is_sys` 角色也能编辑；中途失败会留下「权限被清空」的中间态。包事务、按当前用户已有权限过滤、禁止编辑 `is_sys`、写审计。与 SYS-12 同源。 |
| BE-26 | 管理员可删除自己（策略用错守卫） | `app/Policies/System/AdministratorPolicy.php:43-50` | `Auth::id()` 走默认 `web` 守卫，后台会话在 `backend` 守卫 → 恒为 null，自我保护失效；`deleteAny` 也没有排除自己/超管。改 `Filament::auth()->id()` 或 `auth('backend')->id()`。 |

---

## 五、低优先级问题

| 编号 | 问题 | 位置 | 说明 |
|---|---|---|---|
| BE-27 | Horizon 入口对所有人可见，未登录访问 500 | `Setting/Pages/HorizonMonitor.php:25-44`、`HorizonServiceProvider.php:26` | 所有后台账号可见「队列监控」导航，点开对非超管 403；Gate 闭包对 null 调 `isAdministrator()` 会 500。加 `canAccess()`；闭包改 `$user?->isAdministrator() ?? false`。与 INF-20 同源。 |
| BE-28 | 账变日志列标签重复 | `Finance/Resources/Accounts/RelationManagers/LogsRelationManager.php:30-33` | `amount` 与 `before` 都叫「变动前金额」。改为「变动金额」并按正负着色。 |
| BE-29 | 看板缓存 key 用错守卫 | `Mall/Resources/Orders/Pages/ManageOrders.php:19`、`ManageRefunds.php:19`、`Mall/Widgets/StatsOverview.php:22` | `auth()->id()` 走 `web` 守卫 → 恒 null，所有管理员共用一个 key。改 `Filament::auth()->id()` 并加版本号/失效。 |
| BE-30 | ECS 列表写死 region / 静默失败 | `Foundation/Resources/Aliyuns/RelationManagers/EcsRelationManager.php:25-53` | 固定 `cn-beijing`，其它地域永远「没有服务器」；异常被吞成空列表。region 可配置；失败提示而不是空态。 |

---

## 六、跨面板补充

| 观察 | 位置 | 说明 |
|---|---|---|
| 「身份调整」策略标记 Backend，入口却在租户端 | `UserPolicy.php:78`、`Tenant/.../Users/Tables/UsersTable.php:45` | 平台没有入口，租户反而能改会员身份，权限归属与挂载位置相反。 |
| 租户端可重置任意用户支付密码 | `Actions/User/SetPaymentPasswordAction.php`（挂载于 `Tenant/.../ViewUser.php:20`） | 无当前密码确认即可重置任意用户支付密码 → 可动用用户余额。 |
| 死代码 Action 集合 | `Actions/Mall/DeleteRefund*`、`ApproveRefundAction`、`ShipReturnAction`、`CreateRefundAction`、`Actions/Campaign/DownloadCodeAction` 等 | 后台商城退款表无任何动作，这批 Action 全平台无人调用；`DownloadCodeAction` 若启用还有逐行 N+1。 |
| `TenantFilter`/`TenantSelect` 只列启用租户 | `app/Filament/Tables/Filters/TenantFilter.php:17`、`Forms/Components/TenantSelect.php:17` | 运营无法按已禁用租户筛选/建单，但列表仍展示其数据。 |
| `SystemInfoWidget` 直接读文件 | `Widgets/SystemInfoWidget.php:13-20` | `file_get_contents(storage_path('version'))` 无 `exists` 保护，部署未生成文件时仪表板报错。 |

---

## 七、修复优先级

1. **P0（当天）**：BE-01（订单详情 500）、BE-03（面板准入越权/误锁）、BE-04（永久 Token）、BE-05（身份证 PII）。
2. **P1（本周）**：BE-02（订单不可操作）、BE-11（开票申请无法拒绝等死页面）、BE-07/BE-08/BE-22（资金凭证可删可改）、BE-09（密钥明文）、BE-12（登录页预填密码）。
3. **P2（本迭代）**：BE-06、BE-10、BE-13、BE-14 ~ BE-17（多租户与审核）、BE-25（自授权限）。
4. **P3**：BE-18 ~ BE-21、BE-23 ~ BE-24、BE-26 ~ BE-30（性能、可读性、缓存 key）。
