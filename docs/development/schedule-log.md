# 计划任务执行日志方案

## 一、需求分析

### 当前状态

- `routes/console.php` 中注册了 6 个计划任务（队列修剪、Sanctum 令牌修剪、模型修剪、订单自动完成、身份过期清理、优惠券过期清理），均使用 `->onOneServer()`，且均为**字符串签名注册**（`Schedule::command('app:xxx')`）
- 任务执行情况只能登录服务器看日志文件或 Horizon，无结构化执行记录
- 任务是否执行过、执行耗时多少、是否失败，运营/管理员在后台无法感知
- `app/Console/Commands/` 下已有 `BaseCommand` 基类，命令均使用 PHP Attribute（`#[Signature]` / `#[Description]`）声明签名与描述

### 目标

1. 每个计划任务的每次执行记录一条日志：任务名、开始/结束时间、耗时、执行结果（成功/失败）、异常信息
2. 在 Backend 面板提供日志列表与详情，支持按任务名、状态、时间范围筛选
3. 在 Backend 面板提供数据概览卡片：今日执行次数、成功率、最近一次失败任务
4. 日志自动清理（复用现有 `model:prune` 机制）

### 非目标

- 不做失败通知（钉钉等渠道推送暂不需要，后台概览能看到失败即可；未来需要时再评估）
- 事件监听只记录执行状态与耗时，**不记录任务内部的业务明细**；业务命令需要时（如"自动完成了几笔订单"）通过 4.2 节的 `logContext()` 埋点写入 `context` 字段
- 不做任务的重试/编排，仅做执行记录

---

## 二、方案选型

| 方案 | 说明 | 结论 |
|------|------|------|
| **A. Laravel 任务事件监听（推荐）** | 监听框架自带的 `ScheduledTaskStarting` / `ScheduledTaskFinished` / `ScheduledTaskFailed` 事件，无侵入，覆盖所有计划任务 | ✅ 采用（主链路） |
| B. BaseCommand 上下文埋点 | 命令内主动调用埋点方法写入业务上下文（如处理数量、租户范围），**不负责记录执行状态**，仅补充 `context` 字段 | ✅ 采用（作为 A 的补充） |
| C. spatie/laravel-schedule-monitor | 功能完整（含 ping 心跳、盘古监控），但引入第三方依赖、有自己的表结构，与现有 `model:prune` 等机制重叠 | ❌ 需要外部监控时再评估 |

方案 A + B 组合的关键点：

- A 负责"发生了什么"：何时开始、耗时、成败、异常——由事件自动记录，命令零改动
- B 负责"做了什么"：任务内部的业务摘要（如"完成 37 笔订单"、"清理 5 个租户的过期身份"）——由命令按需调用 `$this->logContext([...])` 写入
- 注册表（Cache）是 A 与 B 之间的桥：A 在任务开始时把 logId 写进缓存，B 在命令内读出来回写 `context`

### 2.1 框架行为事实（Laravel 13.21 实测，实现前必读）

以下结论全部来自 `vendor/laravel/framework/src/Illuminate/Console/Scheduling/*`，与直觉不同的地方已标注 ⚠️：

| 事实 | 出处 | 影响 |
|------|------|------|
| 事件类名为 `ScheduledTaskStarting` / `ScheduledTaskFinished` / **`ScheduledTaskFailed`** | `Events/` 目录 | ⚠️ **没有 `ScheduledTaskFailing`**，命名写错的监听器不会生效 |
| `ScheduledTaskFinished::$runtime`（float 秒）、`ScheduledTaskFailed::$exception`（Throwable） | 事件构造签名 | ⚠️ 属性名是 `runtime` 不是 `runTime` |
| 事件对象 `$task` 是 `Illuminate\Console\Scheduling\Event`；`->command` 是**命令字符串**（`'"php" artisan app:mall:order-auto-complete'`） | `Event::$command` 注释"the command string" | ⚠️ **不是 Symfony Command 实例**，没有 `getName()` |
| `->description` 仅在传 Command 类/实例注册时被框架填成**命令的中文描述**；字符串注册恒为 `null` | `Schedule::command()` | ⚠️ 本项目 6 个任务 `description` 全为 null，且不能拿 description 当标识（与命令侧 `getName()` 对不上） |
| `->expression`（cron）、`->exitCode`、`->timezone`、`->name` 可直接读 | `ManagesAttributes` / `Event` | 可直接落库 |
| 执行顺序：`Starting` → 子进程执行 → `Finished` → **若 `exitCode != 0` 再补发 `Failed`** | `ScheduleRunCommand::runEvent()` | ⚠️ 失败任务会**先收到 Finished 再收到 Failed**，见 4.1 |
| 命令在**独立子进程**执行（`Process::fromShellCommandline`），子进程 stdout/stderr 默认重定向到 `/dev/null`（Windows 为 `NUL`） | `Event::execute()` + `CommandBuilder` | 异常真实堆栈拿不到，需 `->storeOutput()`，见 4.4 |
| 分布式锁在事件**之前**获取：`runSingleServerEvent()` 先 `serverShouldRun()` 拿锁，再 `runEvent()` | `ScheduleRunCommand` | 未抢到锁的节点**一个事件都不发**（连 Skipped 也不发），单机/多机行为一致，符合预期 |
| 过滤未命中（`withoutOverlapping` / `when()` / `environments()` / 维护模式 / `schedule:pause`）只发 `ScheduledTaskSkipped`，**没有 Starting** | `Schedule::filtersPass()` / `Event::$filters` | ⚠️ 被跳过的执行不产生日志行，"没有记录"≠"没执行"，见 4.6 |
| 手动执行命令时框架发的是 `CommandStarting` / `CommandFinished`（由 `ConsoleKernel::rerouteSymfonyCommandEvents()` 把 Symfony `console.command`/`console.terminate` 转派），**`runningUnitTests()` 为真时不开启** | `Foundation/Console/Kernel.php` | 手动留痕挂在后者上，测试需 `WithConsoleEvents`，见 4.7 |
| 调度器拉起的子进程被注入了 `__LARAVEL_CONTEXT` 环境变量 | `Event::execute()` | 这是"我是不是调度子进程"的确定性判据（不依赖缓存/数据库），见 4.7 |
| `Event::storeOutput()` / `Event::mutexName()` 均为 public | `Event.php:361,859` | 输出采集可行，见 4.4 |

### 2.2 监听器注册方式（实施时踩到的坑）

- `app/Listeners` 下的监听器靠**事件自动发现**注册，`DiscoverEvents` 只识别 `handle*` 与 `__invoke` 方法（`Str::is('handle*', $method->name)`）
  → 方法名必须叫 `handleStarting` / `handleFinished` / `handleFailed`（**不能叫 `onTaskStarting`**，那样永远不会被注册）
- ⚠️ 本仓库**已删除** `app/Providers/EventServiceProvider.php`：它从未注册在 `bootstrap/providers.php`，里面的 `$listen` 条目从来没有生效过（原来的两个监听器实际一直是靠自动发现工作的），是纯死配置。**不要重建它**；若将来确实需要显式映射 `$listen` / `$subscribe` / `$observers`，重建时必须同时加进 `bootstrap/providers.php`，并让已在 `app/Listeners` 里被自动发现的类退出发现（实现 `Illuminate\Contracts\Events\ShouldBeDiscovered` 的 `shouldBeDiscovered(): false`），否则同一监听器会被注册两次
- 自动发现用公开方法的**第一个参数类型**判定订阅的事件，因此一个监听器类可以同时订阅多个事件（每个事件对应一个 `handleXxx` 方法）
- `$event->command` 是命令字符串，且 **Windows 下 `php` 与 `artisan` 两段都被引号包裹**：`"D:\php\php85\php.exe" "artisan" app:mall:order-auto-complete`，解析签名必须容忍引号（用正则，不能简单 `afterLast('artisan ')`）

---

## 三、数据模型设计

### 3.1 表结构 `schedule_run_logs`

新建迁移 `database/migrations/0010_00_00_000001_create_schedule_run_logs_table.php`（现有最大编号为 `0009_00_00_000001`）：

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | bigint | 主键 |
| `task` | string(64) | 任务标识：命令签名（如 `app:mall:order-auto-complete`），解析方式见 4.1 |
| `expression` | string(32) | cron 表达式（如 `0 0 * * *`），便于核对调度配置 |
| `server` | string(64) nullable | 执行节点标识，取 `config('custom.server_id')`（`.env` 的 `SERVER_ID`），多机部署排查用 |
| `status` | string(16) | 执行状态：`running` / `success` / `failed`（枚举 `ScheduleRunStatus`） |
| `source` | string(16) | 触发来源：`schedule` 自动调度 / `manual` 命令行手动执行（枚举 `ScheduleRunSource`，默认 `schedule`） |
| `started_at` | timestamp | 开始时间 |
| `finished_at` | timestamp nullable | 结束时间（running 时为 null） |
| `duration_ms` | unsigned integer nullable | 耗时毫秒 |
| `exception` | text nullable | 失败原因，截断至 2000 字符（来源见 4.4，**不是**业务异常原栈） |
| `context` | jsonb nullable | 业务上下文（命令经 `logContext()` 埋点写入，如 `{"completed": 37, "failed": 0}`） |
| `output` | text nullable | 子进程输出尾部摘要（可选，需配合 `->storeOutput()`，见 4.4；不启用时恒为 null） |
| `created_at` | timestamp | 等同 `started_at`，列表默认排序用 |

索引：`(task, created_at)`、`(status, created_at)`、`created_at`（prune 用）。不加外键。

```sql
-- 迁移要点（伪码）
Schema::create('schedule_run_logs', function (Blueprint $table) {
    $table->comment('计划任务执行日志');
    $table->id();
    $table->string('task', 64)->comment('任务标识');
    $table->string('expression', 32)->nullable()->comment('cron 表达式');
    $table->string('server', 64)->nullable()->comment('执行节点');
    $table->string('status', 16)->comment('执行状态');
    $table->timestamp('started_at')->comment('开始时间');
    $table->timestamp('finished_at')->nullable()->comment('结束时间');
    $table->unsignedInteger('duration_ms')->nullable()->comment('耗时(毫秒)');
    $table->text('exception')->nullable()->comment('失败原因');
    $table->jsonb('context')->nullable()->comment('业务上下文');
    $table->text('output')->nullable()->comment('输出摘要');
    $table->timestamp('created_at')->comment('创建时间');
    $table->index(['task', 'created_at']);
    $table->index(['status', 'created_at']);
    $table->index('created_at');
});
```

### 3.2 枚举 `ScheduleRunStatus`

`app/Enums/System/ScheduleRunStatus.php`，遵循现有枚举风格（`HasLabel` / `HasColor`，参考 `LogLevel`）：

| Case | 值 | Label | Color |
|------|-----|-------|-------|
| Running | `running` | 执行中 | `info` |
| Success | `success` | 成功 | `success` |
| Failed | `failed` | 失败 | `danger` |

### 3.3 模型 `ScheduleRunLog`

`app/Models/System/ScheduleRunLog.php`，与 `ApiLog` 同风格（参考 `app/Models/System/ApiLog.php`）：

- `#[Unguarded]` + `#[UsePolicy(ScheduleRunLogPolicy::class)]`
- `Prunable` trait，`prunable()` 返回 90 天前的记录（`now()->subDays(90)`）。⚠️ 现有 `model:prune` 是 **每周** 调度（`routes/console.php:14`，非每日），所以实际保留期为"90 天 + 最多 7 天"，需要更精确的清理粒度时再单独加清理命令
- `const null UPDATED_AT = null;`（只有创建时间语义上的 `started_at`）
- `casts`：`status => ScheduleRunStatus`、`started_at/finished_at => datetime`、`context => 'array'`（jsonb 读写）
- 访问器 `is_running`、`is_failed`、`has_errors`（`context['failed'] > 0`）供 Filament 使用
- `scopeStale()`：`running` 且 `started_at` 早于 1 小时，供排查
- 静态方法：
  - `updateCurrentContext(string $task, array $context): void`（见 4.2）
  - `runKey(Event $event): string` / `taskKey(string $task): string`（注册表 key，见 4.1）
- 可选：`const LABELS = ['app:mall:order-auto-complete' => '订单自动完成', ...]`，供后台把签名展示成中文

### 3.4 工厂

`database/factories/System/ScheduleRunLogFactory.php`，与 `ApiLogFactory` 同风格，供 `tests/Feature/System/ScheduleRunLogTest.php` 使用。

---

## 四、采集机制

### 4.1 事件监听器

`app/Listeners/Schedule/RecordScheduleRunLog.php`，**无需改任何 Provider**——放在 `app/Listeners` 下即被自动发现（方法名必须 `handle*`，见 2.2）：

```
Illuminate\Console\Events\ScheduledTaskStarting  → handleStarting
Illuminate\Console\Events\ScheduledTaskFinished  → handleFinished
Illuminate\Console\Events\ScheduledTaskFailed    → handleFailed   // ⚠️ 不是 ScheduledTaskFailing
```

任务标识解析（本项目 6 个任务均为字符串注册，必须解析命令串）：

```php
protected static function taskName(Event $event): string
{
    if ($event instanceof CallbackEvent) {
        return 'callback:'.substr(sha1((string) $event->getSummaryForDisplay()), 0, 12);
    }

    $command = (string) $event->command;

    // ⚠️ 不能优先用 $event->description：字符串注册时为 null，
    //    传 Command 类注册时是中文描述，与命令侧 getName() 对不上
    // ⚠️ Windows 下 artisan 段带引号：`"D:\php\php.exe" "artisan" app:xxx`
    if (preg_match('/"?(?:artisan|artisan\.php)"?\s+(\S+)/', $command, $matches) === 1) {
        return $matches[1];
    }

    // 兜底：去掉引号后取第一段
    return (string) Str::of($command)->trim('"\' ')->before(' ')->trim();
}
```

注册表 key（两层，解决 4.2 的匹配问题与多机串号）：

```php
// 精确 key：监听器使用，含 cron 表达式与节点，避免同签名不同调度/多机互相覆盖
runKey  = "schedule_run_log:run:{server}:{task}:{expression}"
// 宽 key：命令侧 logContext() 使用，命令拿不到 cron 表达式，只能按签名定位
taskKey = "schedule_run_log:task:{server}:{task}"
```

TTL 统一取 3600 秒：需覆盖"任务从开始到命令内最后一次埋点"的时长。⚠️ 任务执行超过 1 小时后埋点会静默丢失（长任务应在结束前尽早调用）。不使用 static 属性数组（Octane 常驻内存会累积）。

处理逻辑：

```text
handleStarting:
    1. 组装 task / expression / server，insert 一条 status=running 的记录（started_at = created_at = now()）
    2. 登记注册表：runKey（精确，含表达式）+ taskKey（宽，命令埋点用），TTL 3600s
    ⚠️ 整段包 try-catch + report()：日志写入失败绝不能让 schedule:run 崩掉

handleFinished:
    1. 由 runKey 读 logId
    2. 仅当记录仍为 running 时更新：status=success、finished_at、duration_ms = (int) ($event->runtime * 1000)
    ⚠️ 不要在这里清除注册表：exitCode != 0 时框架会紧随其后补发 Failed，
       清掉 key 会导致失败任务被永久记成 success（见 4.5）。靠 TTL 自然过期即可

handleFailed:
    1. 由 runKey 读 logId（此时 Finished 可能已跑过，行内已有 duration_ms，必须保留）
    2. 允许改写 running 与 success 的记录，已是 failed 则跳过（幂等）
    3. duration_ms 兜底：仅当入参未带且行内为空时（before-callback 抛错 / 进程启动失败，
       不会有 Finished），用 now()->diffInMilliseconds(started_at) 计算
       ⚠️ 用 ??= 会踩坑：Failed 的入参本就没有 duration_ms，会把 Finished 写好的值覆盖掉
    4. exception 内容见 4.4
    5. 不清除注册表；不吞异常（监听器本身只记录，异常已由框架 handler 处理）
```

备选简化：如果不想引入 Cache 注册表，`onTaskStarting` 可以不落库，仅把起始时间记入注册表，`Finished`/`Failed` 时**一次性 insert**（status 直接为终态）。缺点：任务执行中后台看不到"执行中"状态，也失去 4.4 埋点的落点。**推荐带 running 状态的完整方案**，便于发现"卡死"的任务。

### 4.2 BaseCommand 上下文埋点（补充链路）

`BaseCommand` 增加 `logContext()` 方法，命令在执行过程中把业务摘要写入当前这条日志的 `context` 字段：

```php
// app/Console/Commands/BaseCommand.php 新增
/**
 * 记录业务上下文到计划任务执行日志
 *
 * 仅在"调度器触发且本次执行仍在进行中"时生效；
 * 手动执行命令时自动 no-op，不影响命令本身。
 *
 * @param  array<string, mixed>  $context  业务摘要（如 ['completed' => 37, 'failed' => 0]）
 */
protected function logContext(array $context): void
{
    if (! $name = $this->getName()) {
        return;
    }

    ScheduleRunLog::updateCurrentContext($name, $context);
}
```

模型配套静态方法 `ScheduleRunLog::updateCurrentContext()`：

- 定位当前日志：读**宽 key** `schedule_run_log:task:{server}:{task}`（命令侧只有签名，没有 cron 表达式；这是与监听器分开两个 key 的原因）
- ⚠️ **必须校验目标行仍为"进行中"**（`finished_at` 为 null）才写入。否则注册表保留到 TTL 期间，任何一次手动执行都会把最近那次调度执行的 `context` 覆盖掉——方案原稿"手动执行自动 no-op"的承诺依赖这一条校验才成立
- 读不到（手动执行、注册表过期、本次执行已结束）直接返回，**不抛错、不建新记录**
- 合并策略：`context` 为 jsonb，多次调用按 key **合并**（后写覆盖同 key），适配"处理完租户 A 记一次、租户 B 记一次"的分步上报
- 合并读改写在同一毫秒内完成即可，调度任务单进程执行，无并发竞争；仍包 try-catch 防御，埋点失败只 `report()` 不影响命令

命令侧使用示例（改造现有命令，注意带上失败计数，见 4.5）：

```php
// OrderAutoCompleteCommand::handle() 中
$count = 0;
$failed = 0;
foreach ($configs as $tenantId => $days) {
    $count += $this->completeForTenant($service, (int) $tenantId, (int) $days);
}
$this->logContext(['completed' => $count, 'failed' => $failed]);
```

约定：`context` 只放**结构化摘要**（数字计数、ID 列表等），不放全文输出（那是 `output` 字段的职责）；单值大小控制在 2KB 内。

### 4.3 概览数据口径

概览卡片与列表共用 `ScheduleRunLog` 查询，不新增数据通道：

```text
今日执行次数   → 今日 started_at 的记录数（不含 running；含手动执行，卡片描述里会带出手动次数）
今日成功率     → 今日 success 数 / 今日终态记录数（无终态记录时展示 "-"）
最近失败任务   → 最近一条 failed 记录（task + started_at + exception 首行）
执行中任务     → 当前 status=running 的记录数（>0 时用 warning 色提示，便于发现卡死）
```

- "今日"按 `config('app.timezone')` 计算，与 `->daily()` 的调度时区一致（全部任务均未指定 `->timezone()`，跟随 app 时区）。⚠️ 6 个任务都是 `daily()`（默认 00:00），概览的"今日"数据会在 00:00:0x 一次性产生，属预期
- 进程被 kill 的判定见 4.5：真正会永久停留在 `running` 的是 **`schedule:run` 父进程**被杀（部署重启、PHP fatal、超时），而不是命令子进程；`scopeStale()` 供排查，不做自动改写

### 4.4 输出与真实异常的采集（可选增强）

⚠️ 这是原方案缺失的一环：`Event::execute()` 用 Symfony Process 执行子进程，**子进程的 stdout/stderr 默认被丢弃**（`Event::$output` 默认 `/dev/null`、Windows 下 `NUL`），而业务异常是在**子进程**里被 Laravel 异常处理器渲染的。因此：

- `ScheduledTaskFailed::$exception` 通常是框架自己 new 的
  `Exception("Scheduled command [php artisan xxx] failed with exit code [1].")`，
  **没有原始异常类名、消息和堆栈**
- 命令内部的逐条失败（如订单完成失败）已在命令里被 `catch` 掉并 `$this->error()`，连非零退出码都不会产生，见 4.5

要拿到真实报错文本，需显式开启输出采集：

```php
// routes/console.php：只加在需要排查的任务上
Schedule::command('app:mall:order-auto-complete')
    ->daily()
    ->onOneServer()
    ->storeOutput();     // 框架把输出重定向追加到 storage/logs/schedule-<sha1(mutexName())>.log
```

- `Event::storeOutput()` / `Event::mutexName()` 均为 public，文件路径可由
  `storage_path('logs/schedule-'.sha1($event->mutexName()).'.log')` 复现
- `onTaskFailed`：读取该文件**尾部** 2000 字符写入 `exception`（异常渲染在前面、堆栈在后，取尾部信息量更大）；`onTaskFinished` 可选读尾部写 `output`
- 代价：该文件是**只追加不轮转**的，需要在维护命令里把 `storage/logs/schedule-*.log` 纳入清理
- 若不想引入这一层：`output` 列保留但恒为空，详情页只展示 exit code 消息 + 引导去 `storage/logs/laravel-*.log` 查真因（`LOG_CHANNEL=daily`，子进程异常会写到那里）

### 4.5 失败判定的边界（⚠️ 决定方案能否达成目标 1）

现状：三个业务命令（`OrderAutoCompleteCommand:74`、`IdentityExpireCommand:35`、`CouponExpireCommand`）都在内部 `catch (Throwable)` 后用 `$this->error()` 打印，最后 `return self::SUCCESS`。推论：

- 逐条业务失败**不会**让任务变成非零退出码 → 不会触发 `ScheduledTaskFailed` → 日志记 `success`
- 只有"进程级"故障（框架启动失败、DB 连接不上、PHP fatal、内存超限）才会 `failed`，且 `exception` 只有 exit code 消息（见 4.4）
- 一旦某任务 `return self::FAILURE`：框架先发 `Finished`（写入 duration_ms + success），再发 `Failed`（改写为 failed 并保留 duration_ms）——只要 4.1 不在 Finished 时清 key，终态就是正确的 `failed`

因此约定：

1. 命令内部必须把失败计数写进 `context`（`logContext(['completed' => $n, 'failed' => $m])`），后台以 `context.failed > 0` 展示 warning 徽章（模型 `has_errors` 访问器），不要试图用 status 表达业务级失败
2. 新建调度命令时，若整体失败必须暴露，用 `return self::FAILURE`（不要静默 `return self::SUCCESS`）
3. 文档/值班口径：`success` 只代表"进程正常退出"，业务结果看 `context`

### 4.6 未记录的口径边界

- 被 `withoutOverlapping()` / `when()` / `environments()` / 维护模式 / `schedule:pause` 跳过的执行，**只发 `ScheduledTaskSkipped`，不产生日志行**（4.1 不处理该事件）。"当天没有该任务的记录"可能是"被跳过"而非"没调度"，值班判断时须知
- 未抢到 `onOneServer()` 分布式锁的节点一个事件都不发（只有控制台提示），符合预期
- 需要区分"跳过"时，可后续扩展第四种状态或写 `context.skipped`，本期不做
- 非计划任务签名的命令（`migrate`、`tinker`、`queue:work` 等）永远不进这张表，见 4.7 的白名单

### 4.7 命令行手动执行（`source=manual`）

调度链路只覆盖自动调度；运维在服务器上手动补跑（`php artisan app:xxx`）走的是另一条链路：

```
app/Listeners/Schedule/RecordManualCommandRun.php
Illuminate\Console\Events\CommandStarting  → handleCommandStarting
Illuminate\Console\Events\CommandFinished  → handleCommandFinished
```

关键事实与决策：

- 这两个事件由 `ConsoleKernel::rerouteSymfonyCommandEvents()` 把 Symfony 的 `console.command` / `console.terminate` 转派而来；**`runningUnitTests()` 为真时不会开启**，测试里要用 `Illuminate\Foundation\Testing\WithConsoleEvents` 打开
- Symfony 在命令抛异常时仍会派发 `console.terminate`（`Application::doRunCommand()` 捕获异常后照常走 TERMINATE），**所以失败的手动执行也能拿到非零退出码**
- **白名单**：只记录已注册为计划任务的签名。来源是 `ScheduledTask::registered()`——直接读 `app(Schedule::class)->events()`（`routes/console.php` 的真实注册结果），**不维护第二份手工清单**，新增/删除任务自动同步，也天然覆盖 3 个框架命令（它们不是 `BaseCommand` 子类，用"基类抽象成员"的方案会漏掉）
- **去重（三层判据，任一层命中即判定"这是调度链路"）**：
  1. **进程标记**：调度器 `Event::execute()` 给子进程注入了 `__LARAVEL_CONTEXT` 环境变量，手动在终端跑不会有 → `getenv('__LARAVEL_CONTEXT') !== false` 即跳过。这是确定性判据，不依赖缓存/数据库是否共享
  2. **注册表兜底**：注册表 `taskKey` 命中且对应记录仍为 `running`（父进程已登记）→ 跳过；记录已落终态则说明是之后的手动执行，照常记录
  3. **数据库兜底（防竞态）**：若前两层都未命中，查询数据库确认是否有 5 分钟内的 running 记录。这解决了"父进程 `RecordScheduleRunLog::handleStarting` 已创建记录但缓存还没写入"的竞态窗口
- **不重复收尾**：`CommandFinished` 只处理本进程登记过的执行（注册表键带 `getmypid()`），因此调度子进程不会去改写父进程那条记录
- 手动记录：`expression` 为 null（没有 cron 表达式）、`source=manual`、耗时由 `started_at → finished_at` 计算（没有框架 runtime）、失败时 `exception` 写 `[手动执行] 命令退出码 N`
- 命令内的 `logContext()` 在手动执行时同样生效（注册表用的是进程级手动键，见 4.2），所以"手动补跑处理了多少条"也看得到
- 已知边界：手动执行恰好在同一签名的调度执行**进行中**时，会被三层判据识别为调度子进程而不记录（窗口极小，可接受；进程标记那一层不受影响）

---

## 五、Filament 展示

### 5.1 资源位置

放在 Backend 面板 `SettingCluster` 下，与 `ApiLogResource` 并列（目录结构照抄 `Resources/ApiLogs`）：

```
app/Filament/Backend/Clusters/Setting/Resources/ScheduleRunLogs/
├── ScheduleRunLogResource.php
├── Pages/
│   ├── ManageScheduleRunLogs.php      # 列表
│   └── ViewScheduleRunLog.php         # 详情
├── Schemas/
│   └── ScheduleRunLogInfolist.php
└── Tables/
    └── ScheduleRunLogsTable.php
```

资源配置要点（对照 `ApiLogResource`）：

- `$model = ScheduleRunLog::class`，`$cluster = SettingCluster::class`
- `modelLabel` / `navigationLabel` = `任务日志`，`navigationGroup` = `维护`（与系统日志/队列监控同组），`navigationSort` = 101（系统日志 100、队列监控 102）
- `getPages()` 只有 `index`（Manage）与 `view`，**不注册 create/edit**（日志只读；如需运营手动清理，参考 `ApiLogsTable` 的 `DeleteBulkAction`，并在 policy 上补 `deleteAny` + `#[PolicyName('删除')]`）

### 5.2 列表 Table

| 列 | 说明 |
|----|------|
| `task` | 任务名，`searchable()`，`copyable()`；有 `ScheduleRunLog::LABELS` 映射时用 `formatStateUsing` 显示中文名 |
| `status` | 徽章列，`badge()`，用枚举颜色；`context.failed > 0` 时叠加 warning 图标/文案（见 4.5） |
| `source` | 来源徽章列（自动调度 / 手动执行），可切换显示；配合 `SelectFilter::make('source')` 只看手动执行 |
| `started_at` | 开始时间，`dateTime()`，默认倒序 |
| `duration_ms` | 耗时，`formatStateUsing` 转人性化（如 `1.2s` / `350ms`） |
| `server` | 执行节点，仅非空时展示 |

过滤器：

- `SelectFilter::make('task')`：选项从 distinct task 动态生成
- `SelectFilter::make('status')`：`ScheduleRunStatus` 枚举
- 时间范围：复用项目已装的 `Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter::make('started_at')`（订单列表同款，带 teleport + 时间选择），不要手搓两个 DatePicker

`poll('30s')` 自动刷新，便于值守观察。

### 5.3 详情 Infolist

- 基本信息：任务名、cron 表达式、执行节点、状态徽章
- 时间与耗时：开始/结束时间、耗时
- 失败原因：`TextEntry` + `->prose()` + 等宽字体（`extraAttributes(['style' => 'font-family: monospace'])`），展示完整 `exception`；并在文案里注明这是退出码摘要，真因见 `storage/logs/laravel-*.log`（未启用 `storeOutput()` 时）
- 业务上下文：`KeyValueEntry::make('context')`（jsonb 键值对展示）；为空时 `->hidden()`
- 输出摘要：同上

### 5.4 概览 Widget

⚠️ `SettingCluster` 目前**没有 Dashboard 页**（只有 `Pages/HorizonMonitor.php`），需要新建：

```
app/Filament/Backend/Clusters/Setting/Pages/Dashboard.php
```

照 `Clusters/Campaign/Pages/Dashboard.php` 的写法：`protected static ?string $cluster = SettingCluster::class;` + `content(Schema $schema)` + `getWidgets()`（`Schemas\Components\Grid::make($this->getColumns())`）；或在其中只挂本 Widget，其余保持默认。

Widget：`app/Filament/Backend/Clusters/Setting/Widgets/ScheduleRunOverviewWidget.php`

- 基类 `Filament\Widgets\StatsOverviewWidget`，参考 `Clusters/Mall/Widgets/StatsOverview.php`
- 指标：今日执行次数、今日成功率、执行中任务数、最近一次失败任务
- 数据口径见 4.3，全部基于 `ScheduleRunLog` 单表查询；外层套 `Cache::remember($key, now()->addMinutes(5), ...)`，避免列表页 `poll('30s')` 时反复打库（缓存 key 带上 `now()->toDateString()`，跨日即失效，避免"今日"数据跨零点错位）
- 若不想新增页面：退而求其次放在列表页 `getHeaderWidgets()`（参考 `ViewLottery` / `ViewRedpack`）
- ⚠️ 看板页把 Widget 渲染为**懒加载子组件**（`x-intersect` + `Loading...` 占位），Widget 的文案不在看板页的 HTML 里；写测试时要在 Widget 自身上做 `Livewire::test(ScheduleRunOverviewWidget::class)->assertSee('今日执行次数')`，看板页只断言 `assertOk()`

### 5.5 权限与角色

- 新模型 `#[UsePolicy]` 会被 `AuthServiceProvider::boot()` 扫描注册进 Gate，`PolicyPermission::tree()` 反射生成权限项，**无需改 Seeder**
- ⚠️ 但新权限**不会自动授予已有角色**：上线后需在「角色」里勾选「计划任务日志 · 列表/详情」，否则 Backend 菜单对非超管不可见
- `ScheduleRunLogPolicy` 参照 `ApiLogPolicy`：`protected string $modelName = '计划任务日志';`、`$groupName = '系统管理';`，`viewAny` / `view` 用 `#[PolicyName('列表'|'详情', type: PolicyType::Page)]`

---

## 六、涉及文件清单

| 类别 | 文件路径 | 操作 |
|------|----------|------|
| **迁移** | `database/migrations/0010_00_00_000001_create_schedule_run_logs_table.php` | 新建 |
| **枚举** | `app/Enums/System/ScheduleRunStatus.php` | 新建 |
| **模型** | `app/Models/System/ScheduleRunLog.php` | 新建 |
| **工厂** | `database/factories/System/ScheduleRunLogFactory.php` | 新建 |
| **策略** | `app/Policies/System/ScheduleRunLogPolicy.php` | 新建（参照 `ApiLogPolicy`） |
| **策略修复** | `app/Policies/System/ApiLogPolicy.php` | 修改（补 `use App\Models\System\ApiLog;`，原文件缺 import，非超管走 `view` 会 TypeError） |
| **监听器** | `app/Listeners/Schedule/RecordScheduleRunLog.php` | 新建（自动发现注册，**不改 Provider**） |
| **埋点基类** | `app/Console/Commands/BaseCommand.php` | 修改（新增 `logContext()`） |
| **命令埋点** | `OrderAutoCompleteCommand` / `IdentityExpireCommand` / `CouponExpireCommand` | 修改（`logContext()` 上报 completed/failed） |
| **调度注册** | `routes/console.php` | 修改（可选：`->storeOutput()`，见 4.4；本次未启用） |
| **资源** | `app/Filament/Backend/Clusters/Setting/Resources/ScheduleRunLogs/*` | 新建（5 个文件：Resource + Manage + View + Infolist + Table） |
| **概览页** | `app/Filament/Backend/Clusters/Setting/Pages/Dashboard.php` | 新建（cluster 原本无 Dashboard，导航名「运行看板」） |
| **概览 Widget** | `app/Filament/Backend/Clusters/Setting/Widgets/ScheduleRunOverviewWidget.php` | 新建 |
| **测试** | `tests/Feature/System/ScheduleRunLogTest.php` | 新建（模型/监听器/埋点/策略） |
| **测试** | `tests/Feature/System/ScheduleRunLogPanelTest.php` | 新建（Backend 列表/筛选/详情/看板渲染冒烟） |
| **维护** | `app/Console/Commands/Maintenance/ClearDataCommand.php` 或 `CleanUnusedFilesCommand.php` | 修改（可选：清理 `storage/logs/schedule-*.log`，仅启用 `storeOutput()` 后需要） |

---

## 七、实施步骤（已完成）

1. **迁移 + 枚举 + 模型 + 工厂** → `database/migrations/0010_00_00_000001_create_schedule_run_logs_table.php`
2. **监听器** → `app/Listeners/Schedule/RecordScheduleRunLog.php`（方法名 `handleStarting/Finished/Failed`，靠自动发现注册，不需要改 Provider）
3. **BaseCommand 埋点** → 新增 `logContext()`；三个业务命令接入 `completed` / `failed` 计数
4. **Policy + Filament 资源 + 看板页 + 概览 Widget**
5. **测试**
   - `tests/Feature/System/ScheduleRunLogTest.php`（15 项）：Starting/Finished 成对写入、耗时换算、`app:xxx --params` 签名解析、CallbackEvent 兜底命名、**Finished 后紧跟 Failed 终态为 failed 且保留耗时**、Failed 无 Finished 时耗时兜底、`logContext()` 合并写入 / 手动执行 no-op / 执行已结束 no-op / 注册表残留防御、`stale()`、Prunable 90 天、耗时格式化、策略放行与拒绝
   - `tests/Feature/System/ScheduleRunLogPanelTest.php`（6 项）：列表渲染、按状态与任务筛选、`context.failed > 0` 的「有失败明细」提示、详情页渲染（含中文任务名与异常）、看板页 + 概览 Widget 渲染、资源只有 index/view 两个页面
6. Pint 格式化 + 全量回归

**未做（可选）**：`routes/console.php` 的 `->storeOutput()`（真实报错采集）、被跳过执行的留痕、`storage/logs/schedule-*.log` 清理。

---

## 八、风险与注意事项

- **失败被误判为成功**：见 4.1/4.5。不要在任何 handler 里清除注册表 key，业务级失败必须靠 `context` 表达
- **埋点是"尽力而为"**：`logContext()` 依赖注册表定位日志，超 TTL(1h) 后调用静默丢失；且必须满足目标行仍为 running。长任务应在结束前尽早调用
- **多机部署**：`onOneServer()` 保证同一任务单节点执行，日志天然单条；若未来有任务去掉 `onOneServer()`，会出现每节点一条记录，`server` 字段用于区分。注册表 key 已含 `server`，不会串号
- **写入失败不得影响调度**：监听器与埋点全部包 try-catch + `report()`。`schedule_run_logs` 若因迁移未执行/DB 异常不可写，`schedule:run` 必须照常运行
- **异常信息有限**：默认情况下 `exception` 只有 exit code 摘要；要保留真实报错文本必须启用 `->storeOutput()`（4.4），并接受 `storage/logs/schedule-*.log` 只增不减（需纳入清理）
- **未记录的边界**：跳过（overlapping/when/维护模式/暂停）与手动执行都不产生日志（4.6），值班口径要写清
- **保留期**：`model:prune` 是**每周**执行，90 天保留实际最长 97 天；如需精确到天，另加清理命令或调小窗口
- **日志风暴**：若某任务每分钟执行且失败，会以 1440 条/天的速度增长；Prunable 窗口可按需调小（如 30 天）
- **时区**：`started_at` 与概览"今日"都按 `config('app.timezone')` 计算；全部任务未指定 `->timezone()`，两者天然一致。将来给任务加 `->timezone()` 时，概览口径需同步考虑

---

## 九、实施记录与偏差

### 9.1 与原稿的差异

| 项 | 原稿 | 实施 |
|----|------|------|
| 监听器注册 | 改 `EventServiceProvider::$listen` | 靠 `app/Listeners` 自动发现；方法名必须 `handle*`。`App\Providers\EventServiceProvider` 未在 `bootstrap/providers.php` 注册，是死配置，**不要接上**（会造成已发现的监听器重复注册） |
| 任务标识解析 | `$event->command?->getName()` | 正则解析命令串（Windows 下 artisan 段带引号） |
| 注册表定位 | 模型静态方法 | 同左（`runKey` / `taskKey` / `rememberRun` / `runLogId` / `updateCurrentContext`） |
| 布尔展示辅助 | 访问器 `is_running` 等 | 方法 `isRunning()` / `isFailed()` / `hasErrors()`（对齐 `HasEasyStatus` 的项目惯例） |
| 导航分组 | `系统`，sort 4 | `维护`，sort 101（与系统日志/队列监控同组，SettingCluster 本就没有「系统」分组） |
| 时间范围筛选 | 两个 DatePicker | 项目已装的 `DateRangeFilter` |
| 概览卡片 | 「概览 Widget」 | 新增 `Setting/Pages/Dashboard.php`（运行看板）+ `ScheduleRunOverviewWidget` |

### 9.2 顺带发现的既有问题

- **`ApiLogPolicy` 缺模型 import**：`view(Authenticatable $user, ApiLog $record)` 中的 `ApiLog` 未 `use App\Models\System\ApiLog;`，解析成 `App\Policies\System\ApiLog`（不存在）。超管因 `Policy::before()` 提前放行不会触发，**非超管带「详情」权限访问 API 记录详情会 TypeError**。已补 import
- **`App\Providers\EventServiceProvider` 曾是死配置**：未注册在任何 Provider 列表，其中的 `$listen` 条目从未生效（现有监听器全靠自动发现生效，恰好没有造成功能缺失）。已删除该文件（2026-09-14），删除后监听器注册与相关测试均验证通过
- **`App\Policies\System\ApiLogPolicy` 无 `deleteAny`，但 `ApiLogsTable` 有 `DeleteBulkAction`**：非超管的删除会被 Gate 拒绝（无策略方法 + 非超管），行为上等价于「只有超管能删」。本次未改，如需放开再补 `deleteAny` 与 `#[PolicyName('删除')]`