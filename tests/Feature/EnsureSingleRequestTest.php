<?php

namespace Tests\Feature;

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnsureSingleRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 用户维度锁持有期间，同一用户的并发请求应被拒绝
     */
    public function test_user_scope_blocks_concurrent_request(): void
    {
        Route::middleware(['auth:sanctum', 'lock:test_user,10'])
            ->get('/__test/lock/user', static fn () => response('ok'));

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $lock = Cache::lock('test_user_'.$user->getKey(), 10);
        $this->assertTrue($lock->get());

        $this->getJson('/__test/lock/user')->assertStatus(429);

        $lock->release();

        $this->getJson('/__test/lock/user')->assertOk();
    }

    /**
     * 资源维度锁应使用模型 route key，而不是模型 JSON
     */
    public function test_resource_scope_uses_model_route_key(): void
    {
        Route::middleware([SubstituteBindings::class, 'lock:test_resource,10,resource,user'])
            ->get('/__test/lock/resource/{user}', static fn (User $user) => response('ok'));

        $user = User::factory()->create();

        $lock = Cache::lock('test_resource_'.$user->getRouteKey(), 10);
        $this->assertTrue($lock->get());

        $this->getJson('/__test/lock/resource/'.$user->getRouteKey())->assertStatus(429);

        $lock->release();

        $this->getJson('/__test/lock/resource/'.$user->getRouteKey())->assertOk();
    }

    /**
     * 组合维度锁应按登录用户 + 路由资源生成 key
     */
    public function test_combined_scope_uses_authenticated_user_and_route_key(): void
    {
        Route::middleware(['auth:sanctum', SubstituteBindings::class, 'lock:test_combined,10,combined,user'])
            ->get('/__test/lock/combined/{user}', static fn (User $user) => response('ok'));

        $actor = User::factory()->create();
        $target = User::factory()->create();
        Sanctum::actingAs($actor);

        $lock = Cache::lock('test_combined_'.$actor->getKey().'_'.$target->getRouteKey(), 10);
        $this->assertTrue($lock->get());

        $this->getJson('/__test/lock/combined/'.$target->getRouteKey())->assertStatus(429);

        $lock->release();

        $this->getJson('/__test/lock/combined/'.$target->getRouteKey())->assertOk();
    }

    /**
     * 请求结束后应释放锁，避免后续请求被误伤
     */
    public function test_lock_is_released_after_request(): void
    {
        Route::middleware(['auth:sanctum', 'lock:test_release,10'])
            ->get('/__test/lock/release', static fn () => response('ok'));

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/__test/lock/release')->assertOk();

        $lock = Cache::lock('test_release_'.$user->getKey(), 10);
        $this->assertTrue($lock->get(), '请求结束后应释放锁');
        $lock->release();
    }
}
