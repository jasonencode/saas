<?php

namespace App\Providers;

use App\Extensions\Filesystem\JasonFilesystem;
use App\Extensions\SmsGateways\DebugGateway;
use App\Extensions\Workflow\Workflow;
use App\Models\Administrator;
use App\Models\Setting;
use App\Models\System;
use App\Models\User;
use Exception;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Overtrue\EasySms\EasySms;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerEasySms();
        $this->registerWorkflow();
    }

    private function registerEasySms(): void
    {
        $this->app->singleton(EasySms::class, function() {
            $easySms = new EasySms(config('easy-sms'));

            $easySms->extend('debug', function($config) {
                return new DebugGateway($config);
            });

            return $easySms;
        });
    }

    private function registerWorkflow(): void
    {
        $this->app->singleton(Workflow::class, function() {
            return new Workflow();
        });
    }

    public function boot(): void
    {
        $this->bootMorphRelationMap();
        $this->bootModuleConfig();
        $this->bootRateLimiter();
        JasonFilesystem::registerFilesystem();
    }

    private function bootMorphRelationMap(): void
    {
        Relation::enforceMorphMap([
            'admin' => Administrator::class,
            'system' => System::class,
            'user' => User::class,
        ]);
    }

    private function bootModuleConfig(): void
    {
        try {
            $configs = [];
            foreach (Setting::get() as $item) {
                $configs[$item->module.'.'.$item->key] = $item->value;
            }
            Config::set($configs);
        } catch (Exception) {
        }
    }

    private function bootRateLimiter(): void
    {
        RateLimiter::for('api', function(Request $request) {
            return Limit::perMinute(config('custom.api_rate_limit'))
                ->by(optional($request->user())->id ?: $request->ip());
        });
        RateLimiter::for('uploads', function(Request $request) {
            return Limit::perMinute(10)
                ->by(optional($request->user())->id ?: $request->ip());
        });
        RateLimiter::for('login', function(Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip());
        });
        RateLimiter::for('sms', function(Request $request) {
            return Limit::perMinute(2)
                ->by($request->ip());
        });
    }

    public function provides(): array
    {
        return [EasySms::class, Workflow::class];
    }
}
