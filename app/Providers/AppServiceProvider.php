<?php

namespace App\Providers;

use App\Extensions\Filesystem\JasonFilesystem;
use App\Extensions\SmsGateways\DebugGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\MasterSupervisor;
use Overtrue\EasySms\EasySms;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        URL::forceHttps(config('custom.force_https'));
    }

    public function boot(): void
    {
        MasterSupervisor::determineNameUsing(static fn() => config('custom.server_id'));
        $this->bootRateLimiter();
        $this->bootEasySms();
        $this->bootBluePrint();
        JasonFilesystem::boot();
    }

    private function bootEasySms(): void
    {
        $this->app->singleton(EasySms::class, function() {
            $easySms = new EasySms(config('easy-sms'));

            $easySms->extend('debug', function($config) {
                return new DebugGateway($config);
            });

            return $easySms;
        });
    }

    private function bootRateLimiter(): void
    {
        RateLimiter::for('api', static function(Request $request) {
            return Limit::perMinute(config('custom.api_rate_limit'))
                ->by(optional($request->user())->id ?: $request->ip());
        });
        RateLimiter::for('uploads', static function(Request $request) {
            return Limit::perMinute(10)
                ->by(optional($request->user())->id ?: $request->ip());
        });
        RateLimiter::for('login', static function(Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip());
        });
        RateLimiter::for('sms', static function(Request $request) {
            return Limit::perMinute(2)
                ->by($request->ip());
        });
    }

    private function bootBluePrint(): void
    {
        Blueprint::macro('tenant', function() {
            if (config('custom.table_use_foreign')) {
                return $this->foreignId('tenant_id')
                    ->nullable()
                    ->index()
                    ->comment('所属租户')
                    ->constrained('tenants', 'id')
                    ->cascadeOnDelete();
            }

            return $this->unsignedBigInteger('tenant_id')
                ->nullable()
                ->index()
                ->comment('所属租户');
        });

        Blueprint::macro('user', function() {
            if (config('custom.table_use_foreign')) {
                return $this->foreignId('user_id')
                    ->index()
                    ->comment('所属用户')
                    ->constrained('users', 'id')
                    ->cascadeOnDelete();
            }

            return $this->unsignedBigInteger('user_id')
                ->index()
                ->comment('所属用户');
        });

        Blueprint::macro('no', function() {
            return $this->string('no', 32)
                ->unique()
                ->comment('订单编号');
        });

        Blueprint::macro('cover', function() {
            return $this->string('cover')
                ->nullable()
                ->comment('封面图片');
        });

        Blueprint::macro('pictures', function() {
            return $this->json('pictures')
                ->nullable()
                ->comment('轮播图');
        });

        Blueprint::macro('easyStatus', function(bool $default = false) {
            return $this->boolean('status')
                ->default($default)
                ->index();
        });
    }
}
