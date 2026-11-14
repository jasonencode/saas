# System 模块 检查报告

- **检查日期**：2026-11-14
- **检查范围**：`app/Models/System/**`、`app/Services/System/**`、`app/Http/Controllers/{System,Auth}/*`（系统相关）、`app/Policies/System/**`、`app/Enums/System/**`、System 相关迁移、后台 Setting/System 集群资源、`tests/Feature/System/**`
- **检查方式**：逐文件走读 + 资源/策略/中间件挂载点交叉核对 + Filament 5 授权源码核对
- **结论**：任务日志 `task` 过滤器与手动执行测试已修复，租户实名资源已补显式租户作用域（本轮已修）。但**授权仍是 fail-open**：租户面板 39 个 Resource 中 37 个覆写 `canAccess()` 绕过 `viewAny`，策略缺方法时 Filament 非严格模式直接放行（可对 `administrators`/`tenants` 执行永久删除）；`isAdministrator()` 又把租户 `is_sys` 角色等同平台超管，构成实际越权与数据毁损路径。

**统计：严重 2 / 高 2 / 中 7（共 11 条）。**

---

## 一、模块概览

| 子域 | 现状 | 主要风险 |
|---|---|---|
| 管理员/角色 | 双面板共用 `Administrator`，租户角色 `is_sys` | 超管语义混用；角色编辑可自我提权 |
| 租户 | 租户/租户管理员/多租户切换 | `app_key` 非唯一；解析缓存 1 小时 |
| 授权 | `strictAuthorization(false)` + 策略 | 缺失方法默认放行；`canAccess` 绕过 |
| 审计/调度 | `event_logs`、`schedule_run_logs`、API 日志 | 日志可被删；API 日志未挂载；手工命令噪声 |
| 令牌 | Sanctum + 租户签名令牌 | nonce 不消费、base64 令牌无消费端 |

---

## 二、严重问题

### SYS-02 ｜ 严重 ｜ 授权（租户面板）

**租户面板 37 个 Resource 覆写 `canAccess()`，整体绕过 `viewAny` 策略**

- **位置**：`app/Filament/Tenant/Clusters/User/Resources/Users/UserResource.php:34-37`（同型见 `OrderResource`、`AccountResource`、`UserRealnameResource`、`IdentityResource` 等）
- **代码要点**：`return UserCluster::canAccess();`；Filament 5 `CanAuthorizeResourceAccess` 只执行 `abort_unless(Resource::canAccess(), 403)`，`ListRecords::authorizeAccess()` 为空。
- **失败场景**：未分配角色、无任何权限点的租户员工（新建 Staffer 即是）只要模块开通即可进入订单、商品、财务、用户、实名、支付配置等所有列表，看到全租户用户信息、实名证件密文/签名链接。
- **修复建议**：删除资源 `canAccess()` 覆写，导航交给 Cluster；如需模块门禁改为 `Cluster::canAccess() && static::canViewAny()`；补「无角色员工访问每个 Resource index 应 403」测试。

### SYS-03 ｜ 严重 ｜ 授权 / 数据毁损

**缺失策略方法时 fail-open：可永久删除超级管理员/租户，并可删除审计日志**

- **位置**：`app/Filament/Backend/Clusters/Setting/Resources/Administrators/Tables/AdministratorsTable.php:48,53-55`、`app/Policies/System/AdministratorPolicy.php:38-61`、`app/Filament/Backend/Clusters/User/Resources/Tenants/Tables/TenantsTable.php:68,74-75`、`app/Policies/System/TenantPolicy.php:43-53`、`ScheduleRunLogPolicy.php:17-33`、`ApiLogPolicy.php:17-27`
- **代码要点**：表里有 `ForceDeleteAction`/`ForceDeleteBulkAction`，策略只有 `deleteAny`、没有 `forceDelete/forceDeleteAny`；`strictAuthorization(false)` 下缺失方法默认 allow；`forceDelete()` 不触发模型 `deleting` 守卫。
- **失败场景**：拥有「管理员列表」权限的账号可永久删除 `id=1` 超管，`超级管理员禁止删除` 守卫被绕过；同样可永久删除租户及其全部数据；有日志查看权限者可批量删除审计。
- **修复建议**：补齐所有表使用到的 ability；`Administrator` 增加 `forceDeleting` 守卫并禁止删除 `isAdministrator()`；开启 `strictAuthorization(true)` 让缺失策略在 CI 暴露。

---

## 三、高优先级问题

### SYS-01 ｜ 高 ｜ 超管语义

**`isAdministrator()` 把「租户 `is_sys` 角色」和「主键=1」都当作平台超管**

- **位置**：`app/Models/System/Administrator.php:59-63`、`app/Contracts/Policy.php:29-31`、`app/Services/User/TenantService.php:26-30`
- **代码要点**：`getKey() === 1 || roles->contains(fn ($role) => $role->is_sys)`；`Policy::before()` 命中即 `return true`。
- **失败场景**：每个租户创建时都会生成 `Xxx.Admin`（`is_sys=true`），被授予该角色的租户管理员通过所有策略；主键 1 在导入/重建库时也会误授予最高权限。
- **修复建议**：拆分 `isPlatformAdministrator()`（独立字段且不属于任何租户）与 `isTenantAdministrator()`；`before()` 与 Horizon 只认平台超管。

### SYS-04 ｜ 高 ｜ 提权

**后台「管理员编辑」可重置任意非 id=1 管理员（含 `is_sys` 超管）密码**

- **位置**：`app/Policies/System/AdministratorPolicy.php:38-41`、`Backend/.../Administrators/Schemas/AdministratorForm.php:33-39`、`AdministratorsTable.php:44`
- **失败场景**：低权后台账号可改掉某超管（id≠1）密码后以其身份登录，完成提权。
- **修复建议**：禁止编辑 `isAdministrator()` 目标；敏感操作要求当前密码确认并写审计。

---

## 四、中优先级问题

### SYS-05 ｜ 中 ｜ 租户令牌

**`tenants.app_key` 非唯一，租户令牌接口可能解析到错误租户**

- **位置**：`database/migrations/0000_01_01_000001_create_administrators_table.php:51-54`、`app/Http/Controllers/Auth/LoginController.php:56`
- **失败场景**：重复 `app_key` 后，`Tenant::where('app_key')->first()` 可能给 A 的凭据签发 B 的令牌。
- **修复建议**：补 `unique('app_key')`（或复合唯一），生成时冲突重试。

### SYS-06 ｜ 中 ｜ 后台 500

**API 日志列表使用不存在的 `duration_ms` 列，排序会 500**

- **位置**：`app/Filament/Backend/Clusters/Setting/Resources/ApiLogs/Tables/ApiLogsTable.php:44-46`、`create_api_logs_table.php:31-33`
- **失败场景**：点「耗时」列头触发 `order by duration_ms` → PostgreSQL 列不存在 500；不排序时该列也永远为空。
- **修复建议**：改为 `duration`，补面板渲染测试。

### SYS-07 ｜ 中 ｜ 提权

**租户角色编辑缺少 `is_sys`/自身角色保护，拥有「角色编辑」权限者可自我提权**

- **位置**：`app/Policies/System/AdminRolePolicy.php:29-33`、`Tenant/.../Roles/Tables/RolesTable.php:43-45`、`Tenant/.../Roles/Pages/EditRole.php:26-43`
- **失败场景**：员工可编辑自己所属角色并勾选全部权限（含资金调账、用户 PII），下次请求生效；也可改写租户系统角色。
- **修复建议**：`is_sys` 与「自己所属角色」禁止编辑；按差量同步权限并写审计。

### SYS-08 ｜ 中 ｜ 缓存一致性

**租户解析缓存 1 小时且无失效，禁用/过期租户最长 1 小时仍可调用 API**

- **位置**：`app/Support/TenantResolver/TenantResolver.php:50-62`
- **失败场景**：运营禁用租户或提前 `expired_at` 后，带 `X-Tenant-Id` 的活动接口仍按旧状态放行。
- **修复建议**：`Tenant` 的 `saved/deleted` 事件 `Cache::forget`，TTL 降到 60s 内，关键写操作前复核数据库状态。

### SYS-09 ｜ 中 ｜ 调度日志

**手动命令留痕没有白名单：所有 artisan 命令都会写入 `schedule_run_logs`，长驻进程永远 Running**

- **位置**：`app/Listeners/Schedule/RecordManualCommandRun.php:31-43,85-116`
- **失败场景**：`queue:work`/`horizon`/`migrate` 都会落一条 `source=manual`，长驻进程永远停在 Running，任务面板噪声与 `stale` 告警失真。
- **修复建议**：恢复白名单语义（仅注册任务记录），或排除长驻/框架命令；超时记录自动收尾。

### SYS-10 ｜ 中 ｜ 令牌安全

**租户令牌 nonce 可重放，且签发的 base64 令牌没有任何消费端**

- **位置**：`app/Http/Controllers/Auth/LoginController.php:52,72-95`、`app/Http/Middleware/AccessTokenAuthenticate.php:25-39`
- **失败场景**：5 分钟窗口内同一签名可无限换取新令牌；返回的 base64 令牌无法用于 `auth:sanctum`，租户令牌实际不可用。
- **修复建议**：`Cache::add("tenant_nonce:...")` 防重放；统一返回原文令牌或提供并注册可用中间件。

### SYS-11 ｜ 中 ｜ 中间件语义

**`GuessAuthenticate` 覆写后不抛认证异常，且已用于 6 条公开路由**

- **位置**：`app/Http/Middleware/GuessAuthenticate.php:16-27`、`routes/apis/campaign.php:23,26,37,59,65`、`routes/apis/mall.php:69`
- **失败场景**：当前用于「可选登录」尚可，但名称易被误用为通用鉴权；挂到写接口会以 `Auth::id()=null` 进入控制器。
- **修复建议**：重命名 `OptionalAuthenticate` 并注明「不拒绝」，禁止用于写接口。

---

## 五、旧问题状态（2026-11-01 → 2026-11-14）

| 旧编号 | 状态 | 说明 |
|---|---|---|
| SYS-01 超管语义 | 仍存在 | 见 SYS-01 |
| SYS-02 租户资源 `canAccess` 绕过 | 仍存在 | 37/39 个资源覆写 |
| SYS-03 GuessAuthenticate | 仍存在 | 使用点扩到 6 处 |
| SYS-04 租户签名换令牌 | 部分修复 | 令牌 2h + abilities；nonce 未消费、base64 无消费端 |
| SYS-05 异常映射 | 部分修复 | PDO/QueryException 已 500；应用异常仍 400 |
| SYS-06 IP 黑名单 | 仍存在 | 未启用、实现错误 |
| SYS-07 租户缓存 1h | 仍存在 | 无失效 |
| SYS-08 支付配置下拉 | 部分修复 | 已按 `tenant_id` 过滤 options；提交仍无 `exists where` |
| SYS-09 任务日志 task 过滤器 | **已修复** | 已加 `SelectFilter` |
| SYS-10 手动执行测试 | **已修复** | 已同步命令名（语义另有 SYS-09） |
| SYS-11 `canAccessPanel` 用租户数量 | 仍存在 | 见面板 BE-03 |
| SYS-12 租户角色编辑提权 | 仍存在 | 见 SYS-07 |
| SYS-13 角色编辑页 N+1 | 仍存在 | 先查 distinct 再逐 policy |

**本轮已修复**：租户实名资源的列表与详情路由绑定统一 `scopeToCurrentTenant()`（`UserRealnameResource`），并新增 `UserRealnameTenantScopeTest`。

---

## 六、修复优先级

1. **P0**：SYS-02（权限整体绕过）、SYS-03（可删超管/审计）。
2. **P1**：SYS-01（超管语义）、SYS-04（重置超管密码）。
3. **P2**：SYS-05/07（令牌、角色提权）。
4. **P3**：SYS-06/08/09/10/11（列名、缓存、日志、nonce、命名）。
