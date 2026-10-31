# Saas.Foundation — Agent 指南

基于 **Laravel 13 + Filament 5** 的企业级 SaaS 基础项目。采用双后台多租户架构：运营端（`backend`）后台、租户端
（`tenant`）后台，以及 Sanctum API。

## Git 与提交

**不要自作主张提交代码。** 未明确要求时，不要执行 `git commit`、`git push`、`git reset`、`git rebase`、
`git commit --amend` 或切换分支。“改一下 / 完善一下 / 修复一下”不等于允许提交。

- 修改代码后：运行 `vendor/bin/pint --dirty --format agent` 和最小范围的相关测试，然后停止并汇报；
  工作区保持未提交，交由用户审阅。
- 只有明确要求时才提交：使用中文 Conventional Commits，遵循 `.claude/skills/git-commit/SKILL.md`
  （例如 `feat(auth): 添加登录验证功能`）。不要提交无关文件、临时文件或生成物。
- 只有明确要求时才推送。`composer push` 会同时推送 `origin` 和 `github`，需要分别汇报结果。
- 未经明确允许，不要重写历史、强制推送（force push）、删除迁移，或合并/重命名已有迁移。
- 即使用户要求提交/推送，也要先运行 Pint 和相关测试；如有已知失败，必须说明，不能静默提交。

## 常用命令

| 任务                              | 命令                                                          |
|-----------------------------------|---------------------------------------------------------------|
| 开发服务器（artisan + vite 并行） | `composer dev`                                                |
| 测试（清理配置后运行完整测试）    | `composer test`                                               |
| 运行单个测试文件                  | `php artisan test --compact tests/Feature/Mall/OrderTest.php` |
| 运行单个测试方法                  | `php artisan test --compact --filter=testName`                |
| 格式化 PHP（收尾前执行）          | `vendor/bin/pint --dirty --format agent`                      |
| 首次初始化                        | `composer setup`                                              |
| 推送到所有 Git 远端               | `composer push`（执行 `docs/scripts/git-push-all.ps1`）       |
| 清理所有缓存                      | `php artisan optimize:clear`                                  |
| Horizon 面板                      | `php artisan horizon`（访问路径 `HORIZON_PATH`）              |

## 测试

- 测试运行在 SQLite `:memory:`，并使用 `QUEUE_CONNECTION=sync`（见 `phpunit.xml`），无需 Postgres/Redis。
- 应用运行时数据库为 PostgreSQL + Redis（队列/会话/缓存）。本地开发使用被 Git 忽略的 `.env.local` 覆盖 `.env`，
  改为 SQLite + 文件驱动（需先创建 `database/database.sqlite`）；服务器上只有 `.env`。
- 只使用 PHPUnit（不使用 Pest）。功能测试（Feature）放 `tests/Feature`，单元测试（Unit）放 `tests/Unit`。测试使用工厂，
  不要直接创建模型。

## 架构 — 双 Filament 面板

配置位于 `app/Providers/{Backend,Tenant}PanelProvider.php`（均继承 `FilamentPanelProvider`）：

| 面板   | id / 路径              | 守卫（Guard） | 资源目录                | 域名环境变量     |
|--------|------------------------|---------------|-------------------------|------------------|
| 运营端 | `backend` / `/backend` | `backend`     | `app/Filament/Backend/` | `BACKEND_DOMAIN` |
| 租户端 | `tenant` / `/tenant`   | `tenant`      | `app/Filament/Tenant/`  | `TENANT_DOMAIN`  |

- 守卫（`config/auth.php`）：`web` → `User` 模型（API/Sanctum）；`backend` + `tenant` → `Administrator` 模型（面板）。
- 多租户使用 Filament 内置的 `->tenant(Tenant::class, 'slug')`（`App\Models\System\Tenant`）+
  `EnsureTenantNotExpired` 中间件，未使用第三方租户扩展。
- 域名路由通过 `config/custom.php` → `custom.domains.{default,api,backend,tenant}_domain` 配置。
- API 路由按模块拆分：`routes/apis/{auth,mall,finance,content,campaign,user,system}.php`，在
  `bootstrap/app.php` 中注册。
- 代码按业务域组织在 `app/{Models,Services,Enums,Policies,Events,Notifications}` 下：Mall、Finance、
  Content、Campaign、BlockChain、User、System、Foundation。
- 服务通过 `service()` 辅助函数解析（`app/Support/helpers.php`），且必须实现
  `App\Contracts\ServiceInterface`。其他辅助函数：`isBackend()`、`list2tree()`、`calculateDistance()`。
- `AppServiceProvider::boot()`：`URL::forceHttps()`、基于 `SERVER_ID` 的 Horizon `MasterSupervisor` 名称、
  命名限流器（`api`/`uploads`/`login`/`sms`/`register`）、自定义 `Blueprint` 宏、结算任务、`JasonFilesystem`。
- 计划任务在 `routes/console.php` 中定义（Laravel `Schedule` 门面）；全部使用 `->onOneServer()`
  （生产环境需要 Redis 缓存锁）。

## 约定

- Pint（`pint.json`）：`laravel` 预设；`single_quote`、`concat_space` none、`no_unused_imports`、
  `trailing_comma_in_multiline`、`phpdoc_order`/`separation`/`trim`；排除 Blade 模板文件。
- 面板默认：暗色模式、`->spa()`、`->topNavigation()`、资源编辑/创建后跳转 `index`、
  `->strictAuthorization(false)`。
- 语言 `zh_CN`；翻译文件位于 `lang/{zh_CN,zh_TW,en}`。

## 注意事项

- **下方 `<laravel-boost-guidelines>` 区块由 `laravel/boost` 管理，不要手动修改**（会被重新生成）。
  只编辑该标签以上的内容。其中的依赖列表是 Boost 模板，不代表本仓库真实依赖，请以 `composer.json` 为准。
  实际 AI 依赖：`laravel/ai` v1.3 **已安装**；Octane/MCP/Prompts **未安装**。
  ⚠️ 已实测：`boost:update` 会吞掉本文件「注意事项」之后、guidelines 块之外的手写内容——运行前先 `git add AGENTS.md`。
- Filament v5：BelongsTo 使用 `Select::make('..')->relationship('rel','name')`；`Repeater` 使用 `->schema()`；
  文件可见性默认是 `private`，需要公开时设置 `->visibility('public')`。
- Vite manifest 报错 → 执行 `npm run build`（或 `composer dev`）。

---

## Agent 行为准则

用于减少大模型（LLM）常见编码错误的行为准则。整体偏保守而非追求速度；简单任务可自行判断。

**如果以下情况变多，说明准则有效：** 差异中的无关改动减少、因过度设计导致的返工减少、先提问再实现而不是先做错再澄清。

### 1. 先思考再编码

**不要假设，不要掩盖困惑，主动说明取舍。**

实现前：

- 明确说明你的假设。不确定就问。
- 存在多种理解时，列出来，不要静默选择。
- 有更简单的方案时直接说；必要时提出反对意见。
- 有不清楚的地方就停下来，指出问题并提问。

### 2. 简单优先

**用最少的代码解决问题，不做投机性设计。**

- 不实现需求之外的功能。
- 不为一次性代码做抽象。
- 不添加未被要求的“灵活性”或“可配置性”。
- 不为不可能发生的场景写错误处理。
- 如果 200 行能写成 50 行，就重写。

问自己：“资深工程师会觉得这过度设计吗？”如果会，就简化。

### 3. 精准改动

**只改必须改的地方，只清理自己制造的问题。**

修改现有代码时：

- 不要“顺手优化”相邻代码、注释或格式。
- 不要重构没有坏的东西。
- 匹配现有风格，即使你有不同偏好。
- 发现无关的死代码，只提出来，不要删除。

当你的改动产生孤儿代码时：

- 删除因你的改动而不再使用的导入语句、变量、函数。
- 未经要求，不要删除原本就存在的死代码。

检验标准：每一行改动都应能直接追溯到用户的需求。

### 4. 目标驱动执行

**定义成功标准，循环验证直到达成。**

把任务转化为可验证的目标：

- “添加校验” → “先为非法输入写测试，再让测试通过”
- “修复缺陷” → “先写能复现问题的测试，再让它通过”
- “重构 X” → “确保重构前后测试都通过”

多步骤任务先给出简要计划：

```
1. [步骤] → 验证：[检查]
2. [步骤] → 验证：[检查]
3. [步骤] → 验证：[检查]
```

强成功标准能让你独立循环；弱标准（“让它能跑”）会不断需要澄清。

### 5. 汇报，不提交

- 结束时简要汇报：修改了哪些文件、执行了哪些命令、测试结果、已知的既有失败。
- 除非明确要求，不要提交、推送或整理 Git 历史；参见上方 **Git 与提交**。
- 任务存在歧义，或改动会影响无关模块时，先提问再继续。

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines
should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an
expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- filament/filament (FILAMENT) - v5
- laravel/ai (AI) - v1.3
- laravel/framework (LARAVEL) - v13
- laravel/horizon (HORIZON) - v5
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v13

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling
  files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature
  tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`,
  `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to
  discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`,
  `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`,
  `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests
  with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
    - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty
  zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters:
  `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and
  scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list
  available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the
  correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other
  things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you
  should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be
  used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to
  use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit`
  to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run
  `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to
  ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting
  issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use
  `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite
  to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or
  helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change
  to a related file).

=== filament/filament rules ===

## Filament

- Filament is a Laravel UI framework built on Livewire, Alpine.js, and Tailwind CSS. UIs are defined in PHP via fluent,
  chainable components. Follow existing conventions in this app.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and
  idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the
  `list-artisan-commands` tool, or run `php artisan --help`.
- Inspect required options before running, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic
values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make ('type')
->options (CompanyType::class)
->required ()
->live (),

TextInput::make ('company_name')
->required ()
->visible (fn (Get $get): bool => $get ('type') === 'business'),

</code-snippet>

Use `Set $set` inside `->afterStateUpdated()` on a `->live()` field to mutate another field reactively. Prefer
`->live(onBlur: true)` on text inputs to avoid per-keystroke updates:

<code-snippet name="Reactive field update" lang="php">
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

TextInput::make ('title')
->required ()
->live (onBlur: true)
->afterStateUpdated (fn (Set $set, ?string $state) => $set (
'slug',
Str::slug ($state ?? ''),
)),

TextInput::make ('slug')
->required (),

</code-snippet>

Compose layout by nesting `Section` and `Grid`. Children need explicit `->columnSpan()` or `->columnSpanFull()`:

<code-snippet name="Section and Grid layout" lang="php">
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

Section::make ('Details')
->components ([
Grid::make (2)->schema ([
TextInput::make ('first_name')
->columnSpan (1),
TextInput::make ('last_name')
->columnSpan (1),
TextInput::make ('bio')
->columnSpanFull (),
]),
]),

</code-snippet>

Use `Repeater` for inline `HasMany` management. `->relationship()` with no args binds to the relationship matching the
field name:

<code-snippet name="Repeater for HasMany" lang="php">
use Filament\Forms\Components\Repeater;

Repeater::make ('qualifications')
->relationship ()
->schema ([
TextInput::make ('institution')
->required (),
TextInput::make ('qualification')
->required (),
])
->columns (),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make ('full_name')
->state (fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Use `SelectFilter` for enum or relationship filters, and `Filter` with a `->query()` closure for custom logic:

<code-snippet name="Table filters" lang="php">
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

SelectFilter::make ('status')
->options (UserStatus::class),

SelectFilter::make ('author')
->relationship ('author', 'name'),

Filter::make ('verified')
->query (fn (Builder $query) => $query->whereNotNull ('email_verified_at')),

</code-snippet>

Actions are buttons that encapsulate optional modal forms and behavior:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;

Action::make ('updateEmail')
->schema ([
TextInput::make ('email')
->email ()
->required (),
])
->action (fn (array $data, User $record) => $record->update ($data)),

</code-snippet>

### Testing

Testing setup (requires `pestphp/pest-plugin-livewire` in `composer.json`):

- Always call `$this->actingAs(User::factory()->create())` before testing panel functionality.
- For edit pages, pass `['record' => $user->id]`, use `->call('save')` (not `->call('create')`), and do not assert
  `->assertRedirect()` (edit pages do not redirect after save).

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire (ListUsers::class)
->assertCanSeeTableRecords ($users)
->searchTable ($users->first ()->name)
->assertCanSeeTableRecords ($users->take (1))
->assertCanNotSeeTableRecords ($users->skip (1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;

livewire (CreateUser::class)
->fillForm ([
'name' => 'Test',
'email' => 'test@example.com',
])
->call ('create')
->assertNotified ()
->assertHasNoFormErrors ()
->assertRedirect ();

assertDatabaseHas (User::class, [
'name' => 'Test',
'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Edit resource test" lang="php">
livewire(EditUser::class, ['record' => $user->id])
    ->fillForm(['name' => 'Updated'])
    ->call('save')
    ->assertNotified()
    ->assertHasNoFormErrors();

assertDatabaseHas (User::class, [
'id' => $user->id,
'name' => 'Updated',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

Use `->callAction(DeleteAction::class)` for page actions, or `->callAction(TestAction::make('name')->table($record))`
for table actions:

<code-snippet name="Calling actions" lang="php">
use Filament\Actions\Testing\TestAction;

livewire (ListUsers::class)
->callAction (TestAction::make ('promote')->table ($user), [
'role' => 'admin',
])
->assertNotified ();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, `Repeater`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Table columns (`TextColumn`, `IconColumn`, etc.): `Filament\Tables\Columns\`
- Table filters (`SelectFilter`, `Filter`, etc.): `Filament\Tables\Filters\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`,
  `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')`
  when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, `Fieldset`, and `Repeater` do not span all columns by default.
- **Use `Select::make('author_id')->relationship('author', 'name')` for BelongsTo fields.** `BelongsToSelect` does not
  exist in v4.
- **`Repeater` uses `->schema()`, not `->fields()`.**
- **Never add `->dehydrated(false)` to fields that need to be saved.** It strips the value from form state before
  `->action()` or the save handler runs. Only use it for helper/UI-only fields.
- **Use correct property types when overriding `Page`, `Resource`, and `Widget` properties.** These properties have
  union types or changed modifiers that must be preserved:
    - `$navigationIcon`: `protected static string | BackedEnum | null` (not `?string`)
    - `$navigationGroup`: `protected static string | UnitEnum | null` (not `?string`)
    - `$view`: `protected string` (not `protected static string`) on `Page` and `Widget` classes

</laravel-boost-guidelines>
