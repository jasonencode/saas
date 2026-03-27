<?php

namespace App\Providers;

use App\Extensions\Filesystem\JasonFilesystem;
use App\Extensions\TenantResolver\TenantResolver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\MasterSupervisor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        URL::forceHttps(config('custom.force_https'));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        MasterSupervisor::determineNameUsing(static fn () => config('custom.server_id'));
        $this->bootRateLimiter();
        $this->bootBluePrint();
        JasonFilesystem::boot();

        Request::macro('tenant', [TenantResolver::class, 'resolve']);
    }

    private function bootRateLimiter(): void
    {
        // API 接口访问频率限制
        RateLimiter::for('api', static function (Request $request) {
            return Limit::perMinute(config('custom.rate_limits.api'))
                ->by(optional($request->user())->id ?: $request->ip());
        });

        // 文件上传频率限制
        RateLimiter::for('uploads', static function (Request $request) {
            return Limit::perMinute(config('custom.rate_limits.upload'))
                ->by(optional($request->user())->id ?: $request->ip());
        });

        // 登录尝试频率限制
        RateLimiter::for('login', static function (Request $request) {
            return Limit::perMinute(config('custom.rate_limits.login'))
                ->by($request->ip());
        });

        // 短信发送频率限制
        RateLimiter::for('sms', static function (Request $request) {
            return Limit::perMinute(config('custom.rate_limits.sms'))
                ->by($request->ip());
        });

        // 用户注册频率限制
        RateLimiter::for('register', static function (Request $request) {
            return Limit::perMinute(config('custom.rate_limits.register'))
                ->by($request->ip());
        });

        // 密码重置频率限制
        RateLimiter::for('password-reset', static function (Request $request) {
            return Limit::perMinute(config('custom.rate_limits.password_reset'))
                ->by($request->ip());
        });

        // 默认频率限制（后备）
        RateLimiter::for('default', static function (Request $request) {
            return Limit::perMinute(config('custom.rate_limits.default'))
                ->by(optional($request->user())->id ?: $request->ip());
        });
    }

    private function bootBluePrint(): void
    {
        Blueprint::macro('tenant', function () {
            return $this->unsignedBigInteger('tenant_id')
                ->nullable()
                ->index()
                ->comment('所属租户');
        });

        Blueprint::macro('user', function () {
            return $this->unsignedBigInteger('user_id')
                ->index()
                ->comment('所属用户');
        });

        Blueprint::macro('no', function () {
            return $this->string('no', 32)
                ->unique()
                ->comment('订单编号');
        });

        Blueprint::macro('cover', function () {
            return $this->string('cover')
                ->nullable()
                ->comment('封面图片');
        });

        Blueprint::macro('pictures', function () {
            return $this->json('pictures')
                ->nullable()
                ->comment('轮播图');
        });

        Blueprint::macro('easyStatus', function (bool $default = false) {
            return $this->boolean('status')
                ->default($default)
                ->index();
        });

        Blueprint::macro('sort', function (int $default = 0) {
            return $this->integer('sort')
                ->default($default)
                ->index()
                ->comment('排序:数字越大越靠前');
        });

        Blueprint::macro('regionAddress', function () {
            $this->unsignedBigInteger('province_id')
                ->index()
                ->nullable()
                ->comment('所属省份');
            $this->unsignedBigInteger('city_id')
                ->index()
                ->nullable()
                ->comment('所属城市');
            $this->unsignedBigInteger('district_id')
                ->index()
                ->nullable()
                ->comment('所属区县');
            $this->string('address')
                ->nullable()
                ->comment('详细地址');
        });
    }
}
