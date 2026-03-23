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
        // MasterSupervisor::determineNameUsing(static fn() => config('custom.server_id'));
        $this->bootRateLimiter();
        $this->bootBluePrint();
        JasonFilesystem::boot();

        Request::macro('tenant', [TenantResolver::class, 'resolve']);
    }

    private function bootRateLimiter(): void
    {
        RateLimiter::for('api', static function (Request $request) {
            return Limit::perMinute(config('custom.api_rate_limit'))
                ->by(optional($request->user())->id ?: $request->ip());
        });
        RateLimiter::for('uploads', static function (Request $request) {
            return Limit::perMinute(10)
                ->by(optional($request->user())->id ?: $request->ip());
        });
        RateLimiter::for('login', static function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip());
        });
        RateLimiter::for('sms', static function (Request $request) {
            return Limit::perMinute(2)
                ->by($request->ip());
        });
    }

    private function bootBluePrint(): void
    {
        Blueprint::macro('tenant', function () {
            return $this->foreignId('tenant_id')
                ->nullable()
                ->index()
                ->comment('所属租户')
                ->constrained()
                ->cascadeOnDelete();
        });

        Blueprint::macro('user', function () {
            return $this->foreignId('user_id')
                ->index()
                ->comment('所属用户')
                ->constrained()
                ->cascadeOnDelete();
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
