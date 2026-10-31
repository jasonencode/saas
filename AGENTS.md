# Saas.Foundation — Agent 指南

基于 **Laravel 13 + Filament 5** 的企业级 SaaS 基础项目：运营端（`backend`）与租户端（`tenant`）双 Filament 面板，外加 Sanctum API。业务按 Mall / Finance / Content / Campaign / BlockChain / User / System / Foundation 分域。

## Git 与提交

**不要自作主张提交代码。** 未明确要求时，不要执行 `git commit`、`git push`、`git reset`、`git rebase`、
`git commit --amend` 或切换分支。“改一下 / 完善一下 / 修复一下”不等于允许提交。

- 修改代码后：运行 `vendor/bin/pint --dirty --format agent` 和最小范围的相关测试，然后停止并汇报；
  工作区保持未提交，交由用户审阅。
- 只有明确要求时才提交：使用中文 Conventional Commits，遵循 `.agents/skills/jc-git-commit/SKILL.md`
  （例如 `feat(auth): 添加登录验证功能`）。不要提交无关文件、临时文件或生成物。
- 只有明确要求时才推送。`composer push` 会同时推送 `origin` 和 `github`，需要分别汇报结果。
- 未经明确允许，不要重写历史、强制推送（force push）、删除迁移，或合并/重命名已有迁移。
- 即使用户要求提交/推送，也要先运行 Pint 和相关测试；如有已知失败，必须说明，不能静默提交。

## 常用命令

| 任务                                 | 命令                                                                      |
|--------------------------------------|---------------------------------------------------------------------------|
| 开发服务器（Horizon + serve + vite） | `composer dev`                                                            |
| 测试（清理配置后运行完整测试）       | `composer test`                                                           |
| 运行单个测试文件                     | `php artisan test --compact tests/Feature/Mall/OrderLifecycleApiTest.php` |
| 运行单个测试方法                     | `php artisan test --compact --filter=testName`                            |
| 格式化 PHP（收尾前执行）             | `vendor/bin/pint --dirty --format agent`                                  |
| 首次初始化                           | `composer setup`                                                          |
| 推送到所有 Git 远端                  | `composer push`（执行 `docs/scripts/git-push-all.ps1`）                   |
| 清理所有缓存                         | `php artisan optimize:clear`                                              |
| Horizon 面板                         | `php artisan horizon`（访问路径 `HORIZON_PATH`）                          |

- `composer dev` 实际执行 `php artisan dev`，会并行启动 Horizon、`artisan serve` 和 `pnpm dev`（`php artisan dev:list` 可查）。本机没有 Redis 时不要用它，改为分别运行 `php artisan serve` 与 `pnpm dev`。
- 前端包管理用 pnpm（仓库有 `pnpm-lock.yaml`）；构建命令是 `pnpm build`。

## 环境与测试

- 只用 PHPUnit（没有 Pest）。`composer test` 会先 `config:clear` 再跑全量测试；单文件/单方法用 `php artisan test --compact ...`。
- `phpunit.xml` 固定 SQLite `:memory:`、`QUEUE_CONNECTION=sync`、`CACHE_STORE=array`，测试不依赖 Postgres/Redis。
- 运行环境默认为 PostgreSQL + Redis（`.env.example`）。**`.env.local` 不是 `.env` 的覆盖层**：只有进程环境已设置 `APP_ENV=local` 或命令带 `--env=local` 时，Laravel 才会整体改用 `.env.local`（不会合并 `.env`）。当前 CLI 默认仍读 `.env`。
- 部分代码是 PostgreSQL 专用：`AppVersionController` 用 `split_part`，`ProductScopes` 用 `jsonb @>`。SQLite 测试会失败或需要跳过，改动这些路径要在 PostgreSQL 上验证。
- Feature 测试常用 `RefreshDatabase` + 工厂。测 Filament 面板需 `actingAs($admin, 'tenant')`、`Filament::setCurrentPanel('tenant')`、`Filament::setTenant($tenant, isQuiet: true)`，参考 `tests/Feature/TenantMallConfigurePageTest.php`。
- PHP 8.5 下 `clue/block-react` 会打印大量 Deprecated 警告，是已知噪音，不代表测试失败。

## 架构 — 双 Filament 面板

配置位于 `app/Providers/{Backend,Tenant}PanelProvider.php`（均继承 `FilamentPanelProvider`）：

| 面板   | id / 路径              | 守卫（Guard） | 资源目录                | 域名环境变量     |
|--------|------------------------|---------------|-------------------------|------------------|
| 运营端 | `backend` / `/backend` | `backend`     | `app/Filament/Backend/` | `BACKEND_DOMAIN` |
| 租户端 | `tenant` / `/tenant`   | `tenant`      | `app/Filament/Tenant/`  | `TENANT_DOMAIN`  |

- 守卫（`config/auth.php`）：`web` → `App\Models\User\User`（Sanctum API）；`backend` + `tenant` → `App\Models\System\Administrator`（面板）。公共面板行为继承 `FilamentPanelProvider`，优先改基类而不是逐个资源。
- 多租户使用 Filament 内置的 `->tenant(Tenant::class, 'slug')`（`App\Models\System\Tenant`）+ `EnsureTenantNotExpired`，未使用第三方租户扩展。
- **租户隔离不是全自动的**：只有 Filament 资源会按 `tenant` 关系注册全局作用域（资源模型需 `BelongsToTenant` 的 `tenant()`）；API、Service、Console 查询必须显式 `->ofTenant($tenant)` 或 `whereBelongsTo($tenant)`。
- 基础模型 `App\Models\Model` 使用 `#[Unguarded]`；不要依赖 `$fillable` 阻止批量赋值，必须在 FormRequest / Filament 表单层限制字段。
- 策略继承 `App\Contracts\Policy`，每个方法加 `#[PolicyName]` 并检查 `$user->hasPermission(__CLASS__, __FUNCTION__)`；模型用 `#[UsePolicy(...)]` 绑定。面板设置了 `->strictAuthorization(false)`，缺少策略/方法时默认放行，新增资源必须补策略。
- 域名路由通过 `config/custom.php` → `custom.domains.{default,api,backend,tenant}_domain` 配置。
- API 路由按模块拆分：`routes/apis/{auth,mall,finance,content,campaign,user,system}.php`；新增路由文件必须在 `bootstrap/app.php` 的 `withRouting(api: [...])` 中注册。
- API 响应用 `App\Http\Responses\ApiResponse`（成功不包 `data` 层）；可预期业务错误直接抛 `InvalidArgumentException` / `RuntimeException`，由 `ApiExceptionHandler` 统一转 400。
- 代码按业务域组织在 `app/{Models,Services,Enums,Policies,Events,Notifications}` 下：Mall、Finance、Content、Campaign、BlockChain、User、System、Foundation。
- 服务通过 `service(FooService::class)` 解析（`app/Support/helpers.php`），且必须实现 `App\Contracts\ServiceInterface`。其他辅助函数：`isBackend()`、`list2tree()`、`calculateDistance()`。
- `AppServiceProvider::boot()` 还注册命名限流器（`api`/`uploads`/`login`/`sms`/`register`/`tenant`）、自定义 `Blueprint` 宏和 `JasonFilesystem`。
- 计划任务在 `routes/console.php` 中定义（Laravel `Schedule` 门面）；全部使用 `->onOneServer()`（生产环境需要 Redis 缓存锁）。

## Filament 5 约定

- 资源结构：`app/Filament/{Backend,Tenant}/Clusters/<域>/Resources/<资源>/{Schemas,Tables,Pages,RelationManagers}`；新资源照同域现有资源拆分，不要把表单/表格堆回 Resource 类。
- BelongsTo 用 `Select::make('x_id')->relationship('x', 'name')`；`Repeater` 用 `->schema()`；Action 统一 `Filament\Actions\*`；图标用 `Heroicon` 枚举。
- 面板公共默认（暗色、`->spa()`、`->topNavigation()`、保存/创建后回 `index`、表格 `striped()`、非原生 Select/DatePicker）都在 `FilamentPanelProvider`，不要在单个资源重复设置。
- 文件可见性：Filament 默认 private，但本仓库全局把 `FileUpload` / `ImageColumn` / `ImageEntry` 设为 `public`；需要私有时显式覆盖。
- 写 Filament / 模型 / PHPDoc 前先加载项目 skill：`.agents/skills/jc-filament-code-style`、`jc-filament-action-style`、`jc-filament-resource-table-style`、`jc-laravel-model-style`、`jc-phpdoc-style`。

## 注意事项

- 收尾前运行 `vendor/bin/pint --dirty --format agent`。Pint 配置为 `laravel` 预设 + `single_quote`、`concat_space: none`、`no_unused_imports`、`phpdoc_order/separation/trim`；Blade 模板不格式化（`pint.json` 的 `not-name`）。
- `laravel/boost` 通过 `boost.json` 管理 guidelines；`boost:update` 会重写/追加区块并可能吞掉手写内容，执行前先 `git add AGENTS.md` 并检查 diff。本仓库真实依赖以 `composer.json` 为准，不要相信 Boost 生成的版本列表。
- Vite manifest 报错 → 执行 `pnpm build`（或 `composer dev`）。
- 默认语言 `zh_CN`；翻译文件位于 `lang/{zh_CN,zh_TW,en}`。
- `app/Console/Commands/Test/*.php` 与 `/modules/*` 在 `.gitignore` 中，不要依赖它们做正式实现。

---

## 工作方式

- 先说明假设；有歧义或多种方案时先问，不静默选择一个。
- 用最少的代码解决当前需求；不为一次性代码做抽象，不加未被要求的配置或兜底。
- 只改需求相关代码；不要顺手重构或格式化相邻代码，也不要删除原有的死代码（因自己改动产生的孤儿代码要清掉）。
- 多步骤任务先给“步骤 → 验证”计划；修 bug 先写能复现的测试，加校验先写失败用例。
- 结束汇报：改动文件、执行的命令、测试结果、已知的既有失败。除非明确要求，不提交/推送/整理 Git 历史。

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `pnpm run build` or ask the user to run `pnpm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
