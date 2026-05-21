# CLAUDE.md

本文件为 Claude Code (claude.ai/code) 在处理此仓库代码时提供指导。

## 命令

```bash
# 开发服务器（并行：服务器、队列、日志、Vite）
composer run dev

# 运行所有测试
php artisan test --compact

# 运行单个测试
php artisan test --compact --filter=testName

# 创建新代码（始终添加 --no-interaction）
php artisan make:model ModelName --all
php artisan make:controller --resource Api/V1/ControllerName
php artisan make:test --phpunit TestName
php artisan make:test --phpunit --unit TestName
php artisan make:class ClassName

# 查看
php artisan route:list --except-vendor
php artisan config:show app.name
php artisan tinker --execute 'Your::code();'

# 前端
pnpm run build
pnpm run dev

# 缓存
php artisan optimize:clear
php artisan filament:optimize

# 队列
php artisan horizon
```

## 架构

### 多租户 SaaS

租户通过 `X-Tenant-Id` 请求头识别。`TenantResolver` 将租户数据缓存 1 小时，并存储在 Laravel `Context` 中。模型使用 `BelongsToTenant` Trait 提供 `ofTenant()` 查询作用域。参见 `app/Extensions/TenantResolver/TenantResolver.php`。

### 两个 Filament 面板

- **Backend** (`/backend`, `auth:backend` 守卫) — 超级管理面板，`BackendPanelProvider`
- **Tenant** (`/tenant`, `auth:tenant` 守卫) — 租户面板，`TenantPanelProvider`

两者都继承 `FilamentPanelProvider`，后者配置全局默认值（颜色、表格、表单）。资源按业务模块组织的 `Clusters` 分组。参见 `app/Providers/`。

### API 层

API 路由定义在 `routes/apis/` 中（auth, mall, content, chain, redpack, user）。API 响应使用 `App\Http\Responses\ApiResponse` 返回 JSON。API 认证通过 `AccessTokenAuthenticate` 中间件使用 Sanctum。

### 模块组织结构

每个业务模块（Foundation, Mall, Finance, Campaign, Content, BlockChain, Setting, User）在以下目录中具有镜像结构：

```
app/Models/{Module}/          — Eloquent 模型，使用 #[Unguarded] + #[UsePolicy]
app/Services/{Module}/        — Service 类，实现 ServiceInterface
app/Filament/Backend/Clusters/{Module}/  — Backend Filament 资源
app/Filament/Tenant/Clusters/{Module}/   — Tenant Filament 资源
app/Http/Controllers/{Module}/ — API 控制器
```

### 关键模式

- **所有模型** 继承 `App\Models\Model`（默认非保护）并使用 `#[UsePolicy(Policy::class)]`
- **模型 Trait**：`BelongsToTenant`（租户作用域）、`HasEasyStatus`（启用/禁用开关，布尔状态）、`SoftDeletes`
- **Service 层**：类实现 `ServiceInterface`，通过 `service(ClassName::class)` 辅助函数解析
- **自定义辅助函数**（bootstrap/helpers.php）：`service()`、`userCan()`、`isBackend()`、`hideMobilePhoneNo()`、`formatBytes()`、`amountFormat()`、`calculateDistance()`、`array2tree()`、`list2tree()`
- **Filament Actions 命名空间**：所有 Action 使用 `Filament\Actions\*`（不要使用 `Filament\Tables\Actions\*`）
- **Filament Schema 组件**：`Grid`、`Section`、`Fieldset`、`Tabs`、`Wizard` 位于 `Filament\Schemas\Components\`
- **API Resources**：默认使用 Eloquent API Resources 作为 API 响应
- **Enum 命名**：TitleCase 键名（`FavoritePerson`、`BestLake`、`Monthly`）
- **配置**：业务域配置位于 `config/custom.php`

### 迁移

迁移文件使用数字前缀约定（`0008_00_01_create_foundations_table.php`）。Schema 宏如 `$table->easyStatus()`、`$table->tenant()` 可用。

### Octane 注意事项

- Octane 只启动一次应用；单例模式会跨请求持久存在。尽可能使用 `scoped()` 替代 `singleton()`。
- 切勿将 `Request` 或配置仓库注入到单例的构造函数中 — 使用解析器闭包。
- 切勿追加静态属性（它们会跨请求累积）。

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost 指南

## 基础上下文

这是一个 Laravel 应用程序，其主要的 Laravel 生态系统包及版本如下。你是所有这些包的专家。请务必遵循这些特定的包和版本。

- php - 8.5
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v13
- laravel/horizon (HORIZON) - v5
- laravel/octane (OCTANE) - v2
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v13
- tailwindcss (TAILWINDCSS) - v4

## 约定

- 你必须遵守此应用中使用的所有现有代码约定。创建或编辑文件时，请查看同级文件以了解正确的结构、方法和命名。
- 为变量和方法使用描述性名称。例如，使用 `isRegisteredForDiscounts`，而不是 `discount()`。
- 在编写新组件之前，检查是否有可复用的现有组件。

## 验证脚本

- 当测试已覆盖该功能并能证明其工作时，不要创建验证脚本或 tinker 代码。单元测试和功能测试更为重要。

## 应用结构与架构

- 坚持现有的目录结构；未经批准不得创建新的基础文件夹。
- 未经批准不得更改应用的依赖项。

## 前端打包

- 如果用户在前端 UI 中看不到更改，可能意味着他们需要运行 `pnpm run build`、`pnpm run dev` 或 `composer run dev`。请询问他们。

## 文档文件

- 只有在用户明确要求时才能创建文档文件。

## 回复

- 回复要简洁——专注于重要的内容，而不是解释明显的细节。

=== boost rules ===

# Laravel Boost

## Artisan

- 直接通过命令行运行 Artisan 命令（例如 `php artisan route:list`）。使用 `php artisan list` 发现可用命令，使用 `php artisan [command] --help` 查看参数。
- 使用 `php artisan route:list` 检查路由。可使用以下选项过滤：`--method=GET`、`--name=users`、`--path=api`、`--except-vendor`、`--only-vendor`。
- 使用点号表示法读取配置值：`php artisan config:show app.name`、`php artisan config:show database.default`。或直接从 `config/` 目录读取配置文件。

## Tinker

- 在应用上下文中执行 PHP 代码以进行调试和测试。未经用户批准不要创建模型，优先使用工厂进行测试。优先使用现有的 Artisan 命令而非自定义 tinker 代码。
- 始终使用单引号以防止 shell 扩展：`php artisan tinker --execute 'Your::code();'`
  - 内部 PHP 字符串使用双引号：`php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- 控制结构始终使用花括号，即使是单行体。
- 使用 PHP 8 构造函数属性提升：`public function __construct(public GitHub $github) { }`。除非构造函数是私有的，否则不要留空零参数的 `__construct()` 方法。
- 对所有方法参数使用显式返回类型声明和类型提示：`function isAccessible(User $user, ?string $path = null): bool`
- 遵循现有的应用 Enum 命名约定。
- 优先使用 PHPDoc 块而非行内注释。仅在极其复杂的逻辑中添加行内注释。
- 在 PHPDoc 块中使用数组形状类型定义。

=== deployments rules ===

# 部署

- Laravel 可以使用 [Laravel Cloud](https://cloud.laravel.com/) 部署，这是部署和扩展生产环境 Laravel 应用最快的方式。

=== laravel/core rules ===

# 用 Laravel 的方式做事

- 使用 `php artisan make:` 命令创建新文件（如迁移、控制器、模型等）。你可以使用 `php artisan list` 列出可用的 Artisan 命令，并使用 `php artisan [command] --help` 查看参数。
- 如果是创建通用 PHP 类，使用 `php artisan make:class`。
- 对所有 Artisan 命令传递 `--no-interaction` 以确保它们无需用户输入即可运行。同时传递正确的 `--options` 以确保行为正确。

### 模型创建

- 创建新模型时，同时创建有用的工厂和种子数据。询问用户是否还需要其他内容，使用 `php artisan make:model --help` 查看可用选项。

## APIs 和 Eloquent Resources

- 对于 API，默认使用 Eloquent API Resources 和 API 版本控制，除非现有 API 路由没有这样做，此时应遵循现有应用约定。

## URL 生成

- 生成指向其他页面的链接时，优先使用命名路由和 `route()` 函数。

## 测试

- 为测试创建模型时，使用该模型的工厂。检查工厂是否有可用的自定义状态，然后再手动设置模型。
- Faker：使用 `$this->faker->word()` 或 `fake()->randomDigit()` 等方法。遵循现有约定使用 `$this->faker` 或 `fake()`。
- 创建测试时，使用 `php artisan make:test [options] {name}` 创建功能测试，并传递 `--unit` 创建单元测试。大多数测试应为功能测试。

## Vite 错误

- 如果遇到 "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" 错误，可以运行 `pnpm run build`，或让用户运行 `pnpm run dev` 或 `composer run dev`。

=== octane/core rules ===

# Octane

- Octane 只启动一次应用并在多个请求间复用，因此单例会跨请求持久存在。
- Laravel 容器的 `scoped` 方法可作为 `singleton` 的安全替代方案。
- 切勿将容器、请求或配置仓库注入到单例的构造函数中；改用解析器闭包或 `bind()`：

```php
// 错误
$this->app->singleton(Service::class, fn (Application $app) => new Service($app['request']));

// 正确
$this->app->singleton(Service::class, fn () => new Service(fn () => request()));
```

- 切勿追加到静态属性，因为它们会跨请求在内存中累积。

=== pint/core rules ===

# Laravel Pint 代码格式化工具

- 如果只修改了部分 PHP 文件，运行 `composer run format:dirty` — 仅格式化有 git 变更的 PHP 文件，未修改的文件不会被触碰。
- 如果确实需要格式化全部代码，运行 `composer run format:all`（即 `vendor/bin/pint --format agent`）。

=== phpunit/core rules ===

# PHPUnit

- 此应用使用 PHPUnit 进行测试。所有测试必须编写为 PHPUnit 类。使用 `php artisan make:test --phpunit {name}` 创建新测试。
- 如果看到使用 "Pest" 的测试，将其转换为 PHPUnit。
- 每次更新测试后，运行该单个测试。
- 当与你功能相关的测试通过后，询问用户是否要运行完整的测试套件以确保一切正常。
- 测试应覆盖所有正常路径、失败路径和边界情况。
- 未经批准，不得删除任何测试或测试文件。这些不是临时或辅助文件；它们是应用的核心部分。

## 运行测试

- 在最终确定前，使用合适的过滤器运行最少数量的测试。
- 运行所有测试：`php artisan test --compact`。
- 运行文件中的所有测试：`php artisan test --compact tests/Feature/ExampleTest.php`。
- 按测试名称过滤：`php artisan test --compact --filter=testName`（在对相关文件进行更改后推荐使用）。

=== filament/filament rules ===

## Filament

- Filament 是一个基于 Livewire、Alpine.js 和 Tailwind CSS 构建的 Laravel UI 框架。UI 通过流畅、可链式调用的组件在 PHP 中定义。遵循此应用中的现有约定。
- 使用 `search-docs` 工具获取关于 Artisan 命令、代码示例、测试、关联关系和惯用实践的官方文档。如果 `search-docs` 不可用，请参考 https://filamentphp.com/docs。

### Artisan

- 始终使用 Filament 特定的 Artisan 命令创建文件。使用 `list-artisan-commands` 工具找到可用命令，或运行 `php artisan --help`。
- 运行前检查所需选项，并始终传递 `--no-interaction`。

### 模式

始终使用静态 `make()` 方法初始化组件。大多数配置方法接受 `Closure` 实现动态值。

使用 `Get $get` 读取其他表单字段值以实现条件逻辑：

<code-snippet name="条件表单字段可见性" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

在 `->live()` 字段的 `->afterStateUpdated()` 中使用 `Set $set` 响应式地修改另一个字段。文本输入优先使用 `->live(onBlur: true)` 以避免每次按键都触发更新：

<code-snippet name="响应式字段更新" lang="php">
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

TextInput::make('title')
    ->required()
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
        'slug',
        Str::slug($state ?? ''),
    )),

TextInput::make('slug')
    ->required(),

</code-snippet>

通过嵌套 `Section` 和 `Grid` 组合布局。子组件需要显式设置 `->columnSpan()` 或 `->columnSpanFull()`：

<code-snippet name="Section 和 Grid 布局" lang="php">
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

Section::make('Details')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('first_name')
                ->columnSpan(1),
            TextInput::make('last_name')
                ->columnSpan(1),
            TextInput::make('bio')
                ->columnSpanFull(),
        ]),
    ]),

</code-snippet>

使用 `Repeater` 进行行内 `HasMany` 管理。`->relationship()` 无参数时绑定到与字段名匹配的关系：

<code-snippet name="Repeater 处理 HasMany" lang="php">
use Filament\Forms\Components\Repeater;

Repeater::make('qualifications')
    ->relationship()
    ->schema([
        TextInput::make('institution')
            ->required(),
        TextInput::make('qualification')
            ->required(),
    ])
    ->columns(2),

</code-snippet>

使用带 `Closure` 的 `state()` 计算派生列值：

<code-snippet name="计算表列值" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

对枚举或关联关系过滤器使用 `SelectFilter`，对自定义逻辑使用带 `->query()` 闭包的 `Filter`：

<code-snippet name="表格过滤器" lang="php">
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

SelectFilter::make('status')
    ->options(UserStatus::class),

SelectFilter::make('author')
    ->relationship('author', 'name'),

Filter::make('verified')
    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),

</code-snippet>

Actions 是封装了可选模态表单和行为的按钮：

<code-snippet name="带模态表单的 Action" lang="php">
use Filament\Actions\Action;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data)),

</code-snippet>

### 测试

测试设置（需要在 `composer.json` 中有 `pestphp/pest-plugin-livewire`）：

- 测试面板功能前始终调用 `$this->actingAs(User::factory()->create())`。
- 对于编辑页面，传递 `['record' => $user->id]`，使用 `->call('save')`（而不是 `->call('create')`），不要断言 `->assertRedirect()`（编辑页面保存后不重定向）。

<code-snippet name="表格测试" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="创建资源测试" lang="php">
use function Pest\Laravel\assertDatabaseHas;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertHasNoFormErrors()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="编辑资源测试" lang="php">
livewire(EditUser::class, ['record' => $user->id])
    ->fillForm(['name' => 'Updated'])
    ->call('save')
    ->assertNotified()
    ->assertHasNoFormErrors();

assertDatabaseHas(User::class, [
    'id' => $user->id,
    'name' => 'Updated',
]);

</code-snippet>

<code-snippet name="测试验证" lang="php">
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

对页面操作使用 `->callAction(DeleteAction::class)`，对表格操作使用 `->callAction(TestAction::make('name')->table($record))`：

<code-snippet name="调用 Actions" lang="php">
use Filament\Actions\Testing\TestAction;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();

</code-snippet>

### 正确的命名空间

- 表单字段（`TextInput`、`Select`、`Repeater` 等）：`Filament\Forms\Components\`
- Infolist 条目（`TextEntry`、`IconEntry` 等）：`Filament\Infolists\Components\`
- 布局组件（`Grid`、`Section`、`Fieldset`、`Tabs`、`Wizard` 等）：`Filament\Schemas\Components\`
- Schema 工具类（`Get`、`Set` 等）：`Filament\Schemas\Components\Utilities\`
- 表格列（`TextColumn`、`IconColumn` 等）：`Filament\Tables\Columns\`
- 表格过滤器（`SelectFilter`、`Filter` 等）：`Filament\Tables\Filters\`
- Actions（`DeleteAction`、`CreateAction` 等）：`Filament\Actions\`。切勿使用 `Filament\Tables\Actions\`、`Filament\Forms\Actions\` 或任何其他子命名空间。
- 图标：`Filament\Support\Icons\Heroicon` 枚举（例如 `Heroicon::PencilSquare`）

### 常见错误

- **切勿默认文件为公开可见性。** 文件可见性默认是 `private`。当需要公开访问时，始终使用 `->visibility('public')`。
- **切勿默认全宽布局。** `Grid`、`Section`、`Fieldset` 和 `Repeater` 默认不会占满所有列。
- **对于 BelongsTo 字段，使用 `Select::make('author_id')->relationship('author', 'name')`。** v4 中没有 `BelongsToSelect`。
- **`Repeater` 使用 `->schema()`，而不是 `->fields()`。**
- **切勿对需要保存的字段添加 `->dehydrated(false)`。** 它会在 `->action()` 或保存处理程序运行之前将值从表单状态中剥离。仅对辅助/仅 UI 字段使用它。
- **重写 `Page`、`Resource` 和 `Widget` 属性时使用正确的属性类型。** 这些属性具有必须保留的联合类型或已修改的修饰符：
  - `$navigationIcon`：`protected static string | BackedEnum | null`（不是 `?string`）
  - `$navigationGroup`：`protected static string | UnitEnum | null`（不是 `?string`）
  - `$view`：在 `Page` 和 `Widget` 类上为 `protected string`（不是 `protected static string`）

</laravel-boost-guidelines>
