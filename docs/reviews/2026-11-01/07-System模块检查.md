# System 模块 检查报告

- **检查日期**：2026-11-01
- **检查范围**：`app/Models/System/*`、`app/Services/System/*`、`app/Policies/System/*`、`app/Contracts/Policy.php`、`app/Support/PolicyPermission/*`、`app/Models/System/Administrator.php`、`app/Providers/HorizonServiceProvider.php`、`app/Filament/Backend/Clusters/Setting/**`、`app/Filament/{Backend,Tenant}/Clusters/User/**`（授权相关）、`routes/apis/{auth,system}.php`
- **检查方式**：逐文件静态走读 + Filament 5 授权源码交叉核对（`CanAuthorizeResourceAccess`、`HasAuthorization`、`DeleteBulkAction`）
- **结论**：双面板 + 策略矩阵 + 权限点（`PolicyName` 反射扫描）设计清晰，日志中心/调度留痕/失败任务/导入导出等运维能力齐全。核心风险在**授权语义**：`isAdministrator()` 把「租户超管」等同于「平台超管」，租户面板资源覆写 `canAccess()` 绕过 `viewAny`，以及若干「fail-open / 写而未用」的控制点。

**统计：严重 1 / 高 2 / 中 6 / 低 4（共 13 条）。**

---

## 一、模块概览

| 组成 | 职责 | 文件 |
|---|---|---|
| 管理员/角色 | 管理员、角色、权限点、租户关联 | `Models/System/{Administrator,AdminRole,AdministratorRole,AdminRolePermission}.php` |
| 策略基类 | `before()` 全局放行、模型名/分组/平台元数据 | `app/Contracts/Policy.php` |
| 权限树 | 反射扫描 `app/Policies`，按 `PolicyName` 构建平台权限树 | `app/Support/PolicyPermission/PolicyPermission.php` |
| 多租户 | `Tenant` 模型、租户权限初始化、到期校验 | `Models/System/Tenant.php`、`Services/User/TenantService.php`、`Middleware/EnsureTenantNotExpired.php` |
| 运维 | API 日志、DB 日志、事件日志、失败任务、任务批、调度日志、导入导出、系统设置、黑名单、敏感词 | `Models/System/*`、`Filament/Backend/Clusters/Setting/**` |
| 审计 | `EventLogService` 统一写入，失败只 `report()` 不阻断业务 | `Services/System/EventLogService.php` |

**正向观察**：
- `RecordScheduleRunLog` 对 `Starting/Finished/Failed` 三个事件的处理相当细致（失败不被 Finished 覆盖、执行注册表、输出尾部采集）。
- `PolicyPermission` 用位掩码区分 Backend/Tenant，权限树与资源策略解耦得较好。
- `EventLogService` 对 User-Agent 截断、请求 ID 回退、写入失败不抛错，符合「日志是旁路」的设计。

---

## 二、严重与高优先级问题

### SYS-01 ｜ 高 ｜ 权限提升
**`isAdministrator()` 把「租户 `is_sys` 角色」判定为「平台超级管理员」**

- **位置**：`app/Models/System/Administrator.php:59-64`、`app/Contracts/Policy.php:27-34`、`app/Services/User/TenantService.php:20-45`、`app/Providers/HorizonServiceProvider.php:26`
- **代码**：
  ```php
  // Administrator.php
  return $this->isAdministratorCache ??= $this->getKey() === 1 || $this->roles->contains(
      static fn (AdminRole $role): bool => (bool) $role->is_sys
  );
  ```
  ```php
  // Policy::before()
  if ($user instanceof Administrator && $user->isAdministrator()) {
      return true;   // 所有策略直接放行
  }
  ```
  ```php
  // TenantService::initializePermissions()：每个租户都会创建 is_sys = true 的角色
  $role = $tenant->roles()->create(['name' => ucfirst($tenant->slug).'.Admin', 'is_sys' => true]);
  ```
- **失败场景**：
  1. 每个租户创建时都会生成 `is_sys=true` 的角色，租户所有者天然持有它；于是「租户管理员」被判定为平台超管，`Policy::before()` 对所有策略直接放行（包括跨租户资源的 `view/update/delete/adjustIdentity`）。
  2. `getKey() === 1` 把「自增 id 恰好为 1」当成超管标识，导入/重建库极易误判。
  3. `viewHorizon` 复用该方法：租户管理员若在 `backend` 守卫下被解析，即可打开 Horizon，查看**全部租户**的队列与失败任务 payload（含订单、用户、支付报文）。
- **建议**：区分 `isPlatformAdministrator()`（独立字段/类型，且不属于任何租户）与 `isTenantAdministrator()`（租户内 `is_sys`）；`Policy::before()` 只对平台超管放行；Horizon 只认平台超管，并对空用户做 `?? false`。

### SYS-02 ｜ 严重 ｜ 授权绕过 / PII
**租户面板全部资源覆写 `canAccess()`，整体绕过 `viewAny` 策略**

- **位置**：`app/Filament/Tenant/Clusters/**/Resources/**/*Resource.php`（共约 40 个资源，例如 `UserResource.php:32-35`、`OrderResource.php:32-35`、`AccountResource.php:32-35`、`UserRealnameResource.php:35-38`、`IdentityResource.php:32-35`）
- **代码**：
  ```php
  public static function canAccess(): bool
  {
      return MallCluster::isAvailable();     // 只判断租户模块是否开通
      // 或 return UserCluster::canAccess();
  }
  ```
- **事实依据**：Filament 5 `CanAuthorizeResourceAccess::authorizeResourceAccess()` 只执行 `abort_unless(static::getResource()::canAccess(), 403)`（`vendor/filament/filament/src/Resources/Pages/Concerns/CanAuthorizeResourceAccess.php`）；`Resource::canAccess()` 默认等于 `canViewAny()`。
- **失败场景**：覆写后 `viewAny` 策略（`hasPermission`）被完全跳过，而这不是个别资源——`app/Filament/Tenant` 下**约 40 个 Resource 全部**采用同一写法。效果：一个没有任何权限点的租户员工（新建 Staffer 未分配角色即如此）也能进入订单、商品、财务、用户、实名、身份、支付配置等所有模块；能看到全租户用户名、昵称、`UserRealname` 身份证密文与身份证照片签名链接。叠加 `->strictAuthorization(false)`，缺失策略时同样放行。
- **建议**：
  1. 全部资源删除 `canAccess()` 覆写，导航可见性交给 Cluster 的 `canAccess()` 控制；
  2. 若确需保留模块门禁，改为 `return XxxCluster::canAccess() && static::canViewAny();`；
  3. 为 `UserRealname` 等敏感资源补 `viewAny` 策略，列表默认掩码，查看明文需二次鉴权 + 审计；
  4. 增加一条自动化测试：无权限角色的 Staffer 访问每个 Resource 的 index 路由应 403。

### SYS-03 ｜ 高（设计风险） ｜ 认证 fail-open
**`GuessAuthenticate` 不抛认证异常，任何误用都会变成「未登录也放行」**

- **位置**：`app/Http/Middleware/GuessAuthenticate.php:16-27`、`routes/apis/mall.php`（当前仅 `products/{product}` 使用 `guess:sanctum`）
- **代码**：
  ```php
  protected function authenticate($request, array $guards): void
  {
      if (empty($guards)) { $guards = ['sanctum']; }
      foreach ($guards as $guard) {
          if ($this->auth->guard($guard)->check()) {
              $this->auth->shouldUse($guard);
          }
      }
      // 没有匹配守卫时既不抛异常，也不返回
  }
  ```
- **失败场景**：父类 `Authenticate::authenticate()` 的契约是「未认证则抛 `AuthenticationException`」。覆写后未认证请求继续进入控制器；当前仅用于「可选登录」的商品详情，属于有意设计，但类名/别名（`guess`）容易被后续开发者当作通用鉴权中间件使用，届时 `Auth::id()` 为 null 的写入接口会以空用户落库或 500。若多个守卫同时命中，`shouldUse` 以最后一个为准，结果不确定。
- **建议**：重命名为 `OptionalAuthenticate` 并在类注释中写明「仅用于可选登录，不做拒绝」；循环内命中即 `return`；新增守卫时避免误用。

---

## 三、中优先级问题

| 编号 | 问题 | 位置 | 说明与建议 |
|---|---|---|---|
| SYS-04 | 租户签名换令牌：nonce 可重放、令牌走 query、消费端未注册 | `LoginController.php:71-89`、`AccessTokenAuthenticate.php:25-46` | `nonce` 只参与签名不落库，5 分钟窗口内可无限重放换取新令牌；`access_token` 放 query 会进访问日志；`AccessTokenAuthenticate` 未在 `bootstrap/app.php` 注册、不校验 `expires_at`/租户状态。建议 `Cache::add("tenant_nonce:...")` 防重放，改用 `auth:sanctum` + 租户状态中间件，令牌走 `Authorization` 头。 |
| SYS-05 | `RuntimeException` 统一映射 400，生产环境返回原始 SQL | `ApiExceptionHandler.php:42-43`、`:58-61` | `QueryException extends PDOException extends RuntimeException`，唯一键冲突、字段超长都会以 400 + `SQLSTATE...` 原样返回。与基础设施 INF-04 / 用户 USER-12 同源。建议只让自定义 `BusinessException` 透出消息，`QueryException/PDOException` 归入 500 并 `report()`。 |
| SYS-06 | IP 黑名单中间件被注释，且实现「先执行后判定」 | `bootstrap/app.php:43-46`、`BlackIpList.php:23-31` | 后台可增删黑名单，但中间件被注释 → 功能整体无效；即便启用也是先执行业务再抛 403，且抛的是 Horizon 的 `ForbiddenException`。改为 `$next` 前判断并返回 `ApiResponse::forbidden()`，补「黑名单 IP 不触达控制器」测试。 |
| SYS-07 | 租户解析缓存 1 小时不失效，禁用/过期租户仍可用 | `TenantResolver.php:45-55` | `Tenant` 的 `saved/deleted` 不清理 `tenant_data:v3:*`；运营禁用租户或提前 `expired_at` 后，最长 1 小时内 API 仍按旧状态放行。建议在模型事件中 `Cache::forget`，TTL 降到 60s 内。 |
| SYS-08 | 租户面板支付配置下拉未按租户限定 | `WechatPaymentForm.php:17-20` | `->options(fn () => Wechat::pluck('name','id'))` 是裸查询，A 租户可见 B 租户公众号并可保存 `wechat_id`，之后支付使用 B 的 `app_id`。改为 `whereBelongsTo(Filament::getTenant())` + `Rule::exists(...)->where('tenant_id', ...)`。 |

---

## 四、低优先级问题

| 编号 | 问题 | 位置 | 说明 |
|---|---|---|---|
| SYS-09 | 调度日志面板缺少 `task` 过滤器 | `ScheduleRunLogsTable.php` | 测试 `ScheduleRunLogPanelTest` 期望 `task` 过滤，实际只有 `status/source/started_at`。补 `SelectFilter::make('task')`（选项来自 `RecordManualCommandRun::getRegisteredTasks()`）或更新测试。 |
| SYS-10 | 手动执行白名单测试未同步新命令 | `tests/Feature/System/ScheduleRunLogManualTest.php:24-33` | 新增 `app:mall:order-auto-sign` 后断言未更新。实现（从调度定义推导）正确，属测试过期。 |
| SYS-11 | `Administrator::canAccessPanel` 用「是否有租户」区分双面板 | `Administrator.php:86-93` | 给后台管理员分配任意租户后，会立即失去 backend 面板访问权；反之租户管理员不能进 backend。语义脆弱（一个账号只能属于一侧）。建议改用显式 `type`/`is_backend` 字段。 |

---

## 五、租户端角色/员工管理（补充）

### SYS-12 ｜ 中 ｜ 权限提升
**拥有「角色编辑」权限的租户员工可以给自己/任意角色授予全部权限**

- **位置**：`app/Filament/Tenant/Clusters/Setting/Resources/Roles/Tables/RolesTable.php:52-54`、`Pages/EditRole.php:35-56`
- **代码**：
  ```php
  Actions\EditAction::make(),          // 没有像 Delete 那样 hidden(is_sys)
  Actions\DeleteAction::make()->hidden(fn (AdminRole $record) => $record->is_sys),
  ```
  ```php
  $record->update(Arr::only($data, ['name', 'description']));
  $record->permissions()->delete();
  // 按提交的 permissions 全量重建
  ```
- **失败场景**：`AdminRolePolicy::update` 只校验「角色管理-编辑」权限，不校验「不能修改自己所属角色」「不能修改 `is_sys` 角色」。因此一个只有角色编辑权限的员工，可以编辑自己所属角色并勾选全部权限（含用户 PII、资金调账），下次请求即生效；也可以把租户系统角色 `Xxx.Admin` 改名/清空权限。删除被隐藏但编辑没有，保护不对称。
- **建议**：`is_sys` 角色禁止编辑（`->hidden()` 或 `abort_unless(!$record->is_sys)`）；禁止编辑「自己所属的角色」；`handleRecordUpdate` 改为差量同步而不是全删重建，并写审计日志。

### SYS-13 ｜ 低 ｜ 性能
**角色编辑页按策略逐条查询权限**

- **位置**：`Pages/EditRole.php:59-76`
- **说明**：`mutateFormDataBeforeFill()` 先查 `distinct(policy)`，再对每个 policy 查一次 `method`；按 40 个策略计算约 80 条 SQL。建议一次 `where('role_id')->get()->groupBy('policy')` 在内存分组。

---

## 六、与基础设施报告的重复项

以下问题在 `01-基础设施与框架检查.md` 已详述，这里只列关联：
- `strictAuthorization(false)`（INF-19）
- 黑名单中间件未启用（INF-11）
- API 日志中间件未启用且记录明文（INF-12）
- Horizon 授权空用户解引用（INF-20）
- 超级管理员 `id === 1` 硬编码（INF-27）

---

## 六、修复优先级

1. **P0（本周）**：SYS-01（超管语义）、SYS-02（PII 授权绕过）。
2. **P1**：SYS-03（GuessAuthenticate 语义）、SYS-04（租户令牌）、SYS-07（租户缓存失效）。
3. **P2**：SYS-05（异常分层）、SYS-06（黑名单）、SYS-08（跨租户下拉）。
4. **P3**：SYS-09 ~ SYS-11（面板与测试一致性）。
