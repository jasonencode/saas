# API 日志记录

系统通过中间件自动记录所有 API 请求日志，用于审计、调试和监控。

## 架构概览

```
Request → ApiRecord 中间件 → ApiLog 模型 → api_logs 表
```

- **中间件**: `App\Http\Middleware\ApiRecord` — 捕获请求/响应数据并写入日志
- **模型**: `App\Models\System\ApiLog` — Eloquent 模型，定义数据结构和自动清理策略
- **数据库**: `api_logs` 表 — 存储所有 API 访问记录

---

## 数据库迁移

文件: `database/migrations/0001_02_00_000001_create_api_logs_table.php`

### 表结构 `api_logs`

| 字段 | 类型 | 索引 | 说明 |
|---|---|---|---|
| `id` | bigint (PK) | — | 自增主键 |
| `user_type` | string, nullable | — | 用户多态类型（关联模型类名） |
| `user_id` | unsigned bigINT, nullable | — | 用户 ID |
| `method` | string(32) | indexed | HTTP 方法（GET/POST/PUT/PATCH/DELETE/OPTIONS/HEAD） |
| `path` | string | — | 请求路径 |
| `ip` | ipAddress | indexed | 客户端 IP 地址 |
| `user_agent` | text, nullable | — | 浏览器 User-Agent |
| `status_code` | unsigned smallint, default 0 | — | HTTP 响应状态码 |
| `duration` | unsigned int, default 0 | — | 请求耗时（毫秒） |
| `input` | longText, nullable | — | 请求入参（JSON 格式） |
| `output` | longText, nullable | — | 响应结果（截断至 1000 字符） |
| `created_at` | timestamp | indexed | 记录时间 |

> 注意：表中没有 `updated_at` 列，日志记录创建后不可变。

### 完整迁移代码

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_logs', static function (Blueprint $table) {
            $table->comment('API访问日志');
            $table->id();
            $table->nullableMorphs('user');
            $table->string('method', 32)
                ->index()
                ->comment('HTTP方法');
            $table->string('path')
                ->comment('请求路径');
            $table->ipAddress('ip')
                ->index()
                ->comment('IP地址');
            $table->text('user_agent')
                ->nullable()
                ->comment('UA');
            $table->unsignedSmallInteger('status_code')
                ->default(0)
                ->comment('状态码');
            $table->unsignedInteger('duration')
                ->default(0)
                ->comment('耗时毫秒');
            $table->longText('input')
                ->nullable()
                ->comment('请求入参');
            $table->longText('output')
                ->nullable()
                ->comment('响应结果');
            $table->timestamp('created_at')
                ->index()
                ->comment('记录时间');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
```

---

## 模型

文件: `app/Models/System/ApiLog.php`

### 完整模型代码

```php
<?php

namespace App\Models\System;

use App\Enums\System\HttpMethod;
use App\Models\Model;
use App\Models\Traits\MorphToUser;
use App\Policies\System\ApiLogPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;

#[Unguarded]
#[UsePolicy(ApiLogPolicy::class)]
class ApiLog extends Model
{
    use MorphToUser,
        Prunable;

    const null UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'method' => HttpMethod::class,
        ];
    }

    /**
     * 获取可修剪的模型查询
     *
     * @return Builder<ApiLog>
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(180));
    }
}
```

### 关键行为

| 特性 | 说明 |
|---|---|
| **多态用户关联** | 通过 `MorphToUser` trait 支持关联任意认证用户模型（`Administrator` 等） |
| **自动修剪** | `Prunable` trait 自动删除 180 天前的日志记录 |
| **不可变记录** | `UPDATED_AT = null`，日志一旦创建不再修改 |
| **枚举类型** | `method` 字段使用 `HttpMethod` 枚举，提供标签和颜色 |

### 修剪策略

```php
public function prunable(): Builder
{
    return static::where('created_at', '<=', now()->subDays(180));
}
```

Laravel 调度任务 `schedule:prune` 会自动执行修剪。

---

## Trait: MorphToUser

文件: `app/Models/Traits/MorphToUser.php`

提供多态用户关联能力，`ApiLog` 通过此 trait 关联到用户模型。

### 完整 Trait 代码

```php
<?php

namespace App\Models\Traits;

use App\Contracts\Authenticatable;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 多态关联用户特征
 *
 * @property string $user_type
 * @property int $user_id
 */
trait MorphToUser
{
    /**
     * 关联用户
     */
    public function user(): MorphTo
    {
        return $this->morphTo()
            ->withoutGlobalScopes();
    }

    /**
     * 设置关联用户
     */
    public function setUserAttribute(Authenticatable $user): void
    {
        $this->attributes['user_type'] = $user->getMorphClass();
        $this->attributes['user_id'] = $user->getKey();
    }
}
```

- `user()` — 多态关联，返回发起请求的用户实例（`withoutGlobalScopes` 确保不受权限范围限制）
- `setUserAttribute()` — 支持 `$apiLog->user = $user` 赋值语法

---

## 枚举: HttpMethod

文件: `app/Enums/System/HttpMethod.php`

定义支持的 HTTP 方法及 Filament 展示用的颜色。

### 完整枚举代码

```php
<?php

namespace App\Enums\System;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum HttpMethod: string implements HasColor, HasLabel
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case OPTIONS = 'OPTIONS';
    case HEAD = 'HEAD';

    public function getLabel(): string
    {
        return match ($this) {
            self::GET => 'GET',
            self::POST => 'POST',
            self::PUT => 'PUT',
            self::PATCH => 'PATCH',
            self::DELETE => 'DELETE',
            self::OPTIONS => 'OPTIONS',
            self::HEAD => 'HEAD',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::GET => 'green',
            self::POST => 'blue',
            self::PUT,
            self::PATCH => 'amber',
            self::DELETE => 'red',
            self::OPTIONS,
            self::HEAD => 'neutral',
        };
    }
}
```

| 方法 | 标签颜色 |
|---|---|
| GET | green |
| POST | blue |
| PUT / PATCH | amber |
| DELETE | red |
| OPTIONS / HEAD | neutral |

---

## 中间件

文件: `app/Http/Middleware/ApiRecord.php`

核心拦截层，在请求通过后记录完整的 API 访问日志。

### 完整中间件代码

```php
<?php

namespace App\Http\Middleware;

use App\Models\System\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiRecord
{
    /**
     * 处理请求，记录API调用日志
     *
     * @param  Request  $request  当前请求
     * @param  Closure  $next  下一步处理
     *
     * @return Response 响应对象
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        try {
            $duration = (int) round((microtime(true) - $start) * 1000);

            $input = $this->parseInput($request);

            $output = method_exists($response, 'getContent') ? $response->getContent() : null;
            if (is_string($output) && strlen($output) > 1000) {
                $output = substr($output, 0, 1000);
            }

            ApiLog::create([
                'user_type' => Auth::user() ? Auth::user()->getMorphClass() : null,
                'user_id' => Auth::id(),
                'method' => $request->getMethod(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status_code' => $response->getStatusCode(),
                'duration' => $duration,
                'input' => $input,
                'output' => $output,
            ]);
        } catch (Throwable) {
        }

        return $response;
    }

    /**
     * 解析请求输入数据
     *
     * @param  Request  $request  当前请求
     *
     * @return string 解析后的输入数据
     * @throws \JsonException
     */
    private function parseInput(Request $request): string
    {
        if ($request->isMethod('GET')) {
            return json_encode($request->all(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        return $request->getContent();
    }
}
```

### 工作流程

```
1. microtime(true)          → 记录请求开始时间
2. $next($request)          → 执行请求，获取响应
3. 计算 duration            → (结束时间 - 开始时间) × 1000，取整
4. parseInput($request)     → GET: JSON 编码 query 参数；其他: 原始请求体
5. 截断 output              → 超过 1000 字符时截断
6. ApiLog::create(...)      → 写入数据库
7. catch (Throwable)        → 静默失败，不影响业务响应
```

### 记录的数据

| 数据 | 来源 | 处理方式 |
|---|---|---|
| `user_type` / `user_id` | `Auth::user()` | 多态关联，未登录为 null |
| `method` | `$request->getMethod()` | 原始 HTTP 方法字符串 |
| `path` | `$request->path()` | 不含域名的请求路径 |
| `ip` | `$request->ip()` | 客户端 IP（支持代理） |
| `user_agent` | `$request->userAgent()` | 原始 UA 字符串 |
| `status_code` | `$response->getStatusCode()` | 响应状态码 |
| `duration` | `microtime()` 差值 | 毫秒，四舍五入取整 |
| `input` | `parseInput()` | GET: JSON 编码的 query 参数；其他: 原始请求体 |
| `output` | `$response->getContent()` | 截断至前 1000 字符 |

---

## Policy

文件: `app/Policies/System/ApiLogPolicy.php`

```php
<?php

namespace App\Policies\System;

use App\Models\System\ApiLog;
use App\Policies\Policy;

class ApiLogPolicy extends Policy
{
    public function viewAny(): bool
    {
        return $this->hasPermission('viewAny');
    }

    public function view(ApiLog $apiLog): bool
    {
        return $this->hasPermission('view');
    }
}
```

授权策略，仅允许拥有 `viewAny` 和 `view` 权限的用户查看日志。

---

## Factory

文件: `database/factories/System/ApiLogFactory.php`

```php
<?php

namespace Database\Factories\System;

use App\Enums\System\HttpMethod;
use App\Models\System\ApiLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiLog>
 */
class ApiLogFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'method' => fake()->randomElement(HttpMethod::cases()),
            'path' => fake()->uuid(),
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'status_code' => fake()->randomElement([200, 201, 301, 400, 404, 500]),
            'duration' => fake()->numberBetween(10, 5000),
            'input' => fake()->text(),
            'output' => fake()->text(),
        ];
    }

    public function createdAt(\DateTimeInterface $createdAt): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $createdAt,
        ]);
    }
}
```

生成随机的 HTTP 方法、路径、IP、状态码（200/201/301/400/404/500）和耗时（10-5000ms）。`createdAt()` 状态可用于设置特定时间戳（修剪测试场景）。

---

## 数据清理

`App\Console\Commands\Maintenance\ClearDataCommand` 中引用了 `ApiLog::class`，用于定期维护清理。

---

## 测试

文件: `tests/Feature/System/ApiLogTest.php`

覆盖以下场景：
- 日志创建
- Prunable 作用域（180 天阈值）
- 修剪行为
- 输入/输出存储
- 用户多态关联
