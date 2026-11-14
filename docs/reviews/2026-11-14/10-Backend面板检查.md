# Backend / Tenant 面板 检查报告

- **检查日期**：2026-11-14
- **检查范围**：`app/Filament/**`（Backend/Tenant Clusters、共享 Forms/Tables/Infolists/Actions/Widgets/RelationManagers、FilamentPanelProvider 及各 PanelProvider）、`app/Policies/**` 中与面板资源对应的策略
- **检查方式**：全量类引用扫描（623 个 import + `X::class`/类型提示）+ 资源/动作/策略逐文件走读 + Filament 5 源码核对
- **结论**：最近修复的 `BannersTable`、`WechatPaymentForm/Table`、`SinglePageForm`、`JsonColumn::formatState()` 引用均已核对正确；**仅剩 2 处失效引用，且是同一个缺陷：两面板 `OrderInfolist` 的 `Order` 类型提示未导入 → 订单详情必 500**。租户隔离因 Filament 5 模型级全局作用域整体改善，但资源普遍用 `canAccess()` 只判断模块开关，绕过 `viewAny` 策略；资金/实名/红包危险动作仍有大量绕过领域服务、无状态约束的实现。

**统计：严重 1 / 高 8 / 中 7（共 16 条）。**

---

## 一、面板概览

| 面板 | 现状 | 主要风险 |
|---|---|---|
| Backend | 7 个 Cluster，资源齐全，含 AI 助手 | 订单详情崩溃、危险删除、PII 可复制、令牌签发无审计 |
| Tenant | 模块开关 + Filament 租户作用域 | `canAccess()` 覆盖策略；跨租户统计；密钥表单明文 |
| 共享组件 | Forms/Tables/Infolists 已大量抽取复用 | `UserInfoColumn` 搜索硬编码关系；空操作菜单 |

---

## 二、严重问题

### BE-01 ｜ 严重 ｜ 运行时错误

**订单详情必崩：两面板 `OrderInfolist` 使用未导入的 `Order`**

- **位置**：`app/Filament/Backend/Clusters/Mall/Resources/Orders/Schemas/OrderInfolist.php:79`、`app/Filament/Tenant/Clusters/Mall/Resources/Orders/Schemas/OrderInfolist.php:74`
- **代码要点**：`->visible(fn (Order $record): bool => bccomp(...))`，文件只导入了 `FulfillmentType/UserEntry/Infolists/Schemas/TextSize`，没有 `use App\Models\Mall\Order`。
- **失败场景**：类型解析到不存在的 `...\Schemas\Order`，传入真实模型触发 TypeError，任意运营/租户人员打开订单详情 500。
- **修复建议**：两处补 `use App\Models\Mall\Order`；补 `ViewOrder` 渲染用例（当前测试未覆盖）。

---

## 三、高优先级问题

### BE-02 ｜ 高 ｜ 授权

**租户资源 `canAccess()` 覆盖 `viewAny`，列表页不校验权限**

- **位置**：`app/Filament/Tenant/Clusters/Mall/Resources/Orders/OrderResource.php:34-37`（租户面板 39 个 Resource 中有 37 个覆写 `canAccess()`）
- **失败场景**：Filament 5 的 `ListRecords::authorizeAccess()` 为空，页面准入只看 `Resource::canAccess()`；无订单/财务/用户权限的员工仍可打开列表，看到本租户订单、用户、实名、资金数据。
- **修复建议**：改为 `parent::canAccess() && 模块判断`，或把模块判断并入 Cluster/策略。

### BE-03 ｜ 高 ｜ 面板准入

**面板准入按「是否属于租户」判定，忽略管理员 `type`**

- **位置**：`app/Models/System/Administrator.php:86-93`
- **失败场景**：运营员工被移出所有租户后自动获得 backend 登录资格；运营超管被加入任一租户即被锁出后台。
- **修复建议**：按 `type` 判定（Admin→backend，Tenant→tenant 且 tenants 非空）；Attach 限定 `type=Tenant`。

### BE-04 ｜ 高 ｜ 令牌

**打开弹窗即签发永久全权 API Token**

- **位置**：`app/Filament/Actions/User/GenerateTokenAction.php:34`
- **失败场景**：每点一次新建一个永久、全权限令牌并明文展示，管理员可事后无痕冒充用户。
- **修复建议**：确认后签发短 TTL 令牌，记录签发人，提供吊销入口。

### BE-05 ｜ 高 ｜ 隐私 / 数据留存

**实名 PII 明文可复制，KYC 记录可批量/强制删除且无删除策略**

- **位置**：`app/Filament/Backend/Clusters/User/Resources/Realnames/Tables/RealnamesTable.php:29-32,64-70`、`app/Policies/User/UserRealnamePolicy.php`
- **失败场景**：身份证号整列可复制导出；有列表权限者可物理删除实名留痕；租户端同样可批量删除。
- **修复建议**：列表掩码并去掉 `copyable`；完整证件仅详情页 + 独立权限 + 审计；补删除策略并移除强制删除。

### BE-06 ｜ 高 ｜ 业务一致性

**批量审核绕过 `ProductService`，可把任意状态商品直接上架**

- **位置**：`app/Filament/Actions/Mall/ProductBulkAuditAction.php:41-45`
- **失败场景**：勾选已下架/已驳回商品点「批量审核」全部变上架，无状态校验、无事务、无 `ProductLog`。
- **修复建议**：逐条调用 `ProductService::audit()` 并过滤 Pending，失败汇总，整批包事务。

### BE-07 ｜ 高 ｜ 资金 / 删除

**提现单/支付退款单可无条件批量 + 强制删除**

- **位置**：`app/Filament/Backend/Clusters/Finance/Resources/WithdrawOrders/Tables/WithdrawOrdersTable.php:70-76`、`app/Filament/Tables/Finance/RefundsTable.php:82-88`、`WithdrawOrderPolicy.php:40-51`、`PaymentRefundPolicy.php:41-51`
- **失败场景**：Pending/Paid/Completed 提现单、Processing/Completed 退款单可被物理删除，钱已出、单已无。
- **修复建议**：`delete` 仅允许终态（Rejected/Cancelled/Failed）；移除强制删除或加二次确认 + 权限 + 审计。

### BE-08 ｜ 高 ｜ 红包码

**红包码金额/状态可在关系管理器修改、Active 码可删**

- **位置**：`app/Filament/Backend/Clusters/Campaign/Resources/Redpacks/RelationManagers/CodesRelationManager.php:30-46,88-91`（Tenant 同构）
- **失败场景**：已发行资金凭证金额可被 5→500，Active 码可删除，领取与支出统计失真；`RedpackCode` 无策略。
- **修复建议**：关系管理器只读；冲正/作废走带权限与备注的 Action；补 `RedpackCodePolicy`。

### BE-09 ｜ 高 ｜ 密钥展示

**云/支付/租户密钥在编辑表单可回显明文**

- **位置**：`app/Filament/Forms/Foundation/AliyunForm.php:25-28`、`WechatForm.php:30-33`、`WechatPaymentForm.php:35-47`、`AlipayForm.php:30-45`、`TenantForm.php:73-82`
- **失败场景**：`password()->revealable()` 点眼睛即明文；私钥/平台证书用 Textarea 直接显示；截图/录屏即可拿到凭据。
- **修复建议**：`revealable(false)` + 空值表示不修改；查看明文单独权限 + 审计。

---

## 四、中优先级问题

### BE-10 ｜ 中 ｜ 类型口径

**租户端新建员工写成 `type=Admin`，与后台员工列表口径相反**

- **位置**：`app/Filament/Tenant/Clusters/Setting/Resources/Staffers/Schemas/StafferForm.php:19-20`、`Backend/.../Staffers/Tables/StaffersTable.php:18`
- **失败场景**：租户创建的员工在后台员工管理看不到，却出现在 `type=Admin` 的列表；解除租户后落入 backend 分支。
- **修复建议**：租户端改为 `AdminType::Tenant`，创建逻辑强制覆盖。

### BE-11 ｜ 中 ｜ 登录安全

**登录页直接采信 URL 的 username/password 且默认记住我**

- **位置**：`app/Filament/Tenant/Pages/Auth/LoginPage.php:25-31`、`app/Filament/Actions/Tenant/StafferLoginAction.php:27-30`
- **失败场景**：可构造 `.../login?username=x&password=y` 钓鱼链接，密码进入浏览器历史/Referer/代理日志。
- **修复建议**：仅接收 username；remember 默认关闭；代登录改一次性签名 token 并审计。

### BE-12 ｜ 中 ｜ 后台能力缺口

**运营端订单全流程无入口，发票列表出现空操作菜单**

- **位置**：`app/Filament/Backend/Clusters/Mall/Resources/Orders/Tables/OrdersTable.php:58-79`、`Pages/ViewOrder.php:25-27`、`Invoices/Tables/InvoicesTable.php:52-56`
- **失败场景**：后台客服无法取消/改地址/发货；发票详情只能手拼 URL，行尾出现空白操作下拉。
- **修复建议**：复用租户端 Action 集合；发票补 `ViewAction`、删空 `ActionGroup`。

### BE-13 ｜ 中 ｜ 表单租户/唯一性

**表单层租户字段与唯一性缺陷**

- **位置**：`app/Filament/Forms/Mall/CategoryForm.php:16-34`、`Backend/.../Contents/Schemas/ContentForm.php:86-96`、`app/Filament/Forms/Content/SinglePageForm.php:35-38`
- **失败场景**：后台新建分类 `tenant_id=null`（孤儿数据）、父级树混租户；A 租户文章可挂 B 租户标签；A 租户占用 slug 后 B 租户无法建同名页面。
- **修复建议**：补租户选择与过滤；slug 改租户内唯一并补 DB 唯一键。

### BE-14 ｜ 中 ｜ 角色权限

**角色权限编辑「先删后建」无事务，可给自己勾任意权限**

- **位置**：`app/Filament/Backend/Clusters/Setting/Resources/Roles/Pages/EditRole.php:28-45`、`Schemas/RoleForm.php:86-96`
- **失败场景**：中途失败留下「权限被清空」；有角色编辑权限者可把管理员删除/提现审批/余额调账勾给自己。
- **修复建议**：包事务、按操作者已有权限过滤、禁止编辑 `is_sys`、写审计。

### BE-15 ｜ 中 ｜ 支付密码

**租户端可直接重置任意用户支付密码**

- **位置**：`app/Filament/Tenant/Clusters/User/Resources/Users/Pages/ViewUser.php:19-23`、`app/Filament/Actions/User/SetPaymentPasswordAction.php:31-56`
- **失败场景**：有权限的员工可为任意本租户用户重设支付密码，随后动用余额。
- **修复建议**：增加操作者密码确认/超管审批，限定范围并审计。

### BE-16 ｜ 中 ｜ 共享组件

**`UserInfoColumn` 搜索硬编码 `user` 关系，`created_by` 列搜索 500**

- **位置**：`app/Filament/Tables/Columns/UserInfoColumn.php:18-26`、`app/Filament/Tables/Finance/RefundsTable.php:35`
- **失败场景**：`PaymentRefund` 只有 `creator()`（morphTo），没有 `user` 关系 → 财务退款列表搜索触发 RelationNotFoundException。
- **修复建议**：按 `$name` 或 morph 分支 `whereMorphedTo`，补搜索用例。

---

## 五、旧问题状态（2026-11-01 → 2026-11-14）

- **仍存在**：BE-01、BE-02、BE-03、BE-04、BE-05、BE-06、BE-10、BE-12、BE-13、BE-14、BE-16、BE-17、BE-20、BE-21、BE-22、BE-23、BE-24、BE-25、BE-27、BE-28、BE-30。
- **部分修复**：BE-07（`Mall\Refund` 策略已加状态约束，`WithdrawOrder`/`PaymentRefund` 仍无）、BE-08（状态收敛、编辑/删除受限；金额仍可改、无策略）、BE-09（模型已加密 + `#[Hidden]`，表单仍明文）、BE-11（发票开票动作已行内化，仍有空菜单）、BE-15（分类树已按租户过滤，内容标签未过滤）、BE-18（部分 Widget 已加缓存）、BE-19（部分统计口径仍错）、BE-29（`Auth::id()` 在面板内可用，旧判断不成立；缓存失效仍有问题）。
- **不成立**：BE-26 的「`Auth::id()` 恒为 null」不成立（Filament 会 `shouldUse` 当前 guard）；残留低危是批量删除未排除自己。
- **已验证修复**：`BannersTable`、`WechatPaymentForm/Table`、`SinglePageForm` 引用正确；`JsonColumn::formatState(mixed, ?Model)` 与 Filament 5 签名兼容。

---

## 六、修复优先级

1. **P0**：BE-01（订单详情必崩）。
2. **P1**：BE-02（权限绕过）、BE-03（面板准入）、BE-05（PII/删除）、BE-07（资金删除）、BE-04（永久令牌）。
3. **P2**：BE-06/08/09（批量审核、红包码、密钥）。
4. **P3**：BE-10~BE-16。
