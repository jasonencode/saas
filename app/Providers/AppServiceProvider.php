<?php

namespace App\Providers;

use App\Services\Finance\TaskService;
use App\Support\Filesystem\JasonFilesystem;
use App\Support\Tasks\DirectReward;
use App\Support\Tasks\SecondReward;
use Carbon\CarbonInterface;
use Carbon\FactoryImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\MasterSupervisor;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        URL::forceHttps(config('custom.force_https'));
    }

    public function boot(): void
    {
        FactoryImmutable::getDefaultInstance()
            ->settings([
                'toJsonFormat' => static fn (CarbonInterface $date): string => $date->utc()->format('Y-m-d\TH:i:s\Z'),
            ]);

        MasterSupervisor::determineNameUsing(static fn () => config('custom.server_id'));
        $this->bootRateLimiter();
        if ($this->app->runningInConsole()) {
            $this->bootBluePrint();
        }
        $this->bootSettlementTasks();
        JasonFilesystem::boot();
    }

    protected function bootRateLimiter(): void
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

        // 登录尝试频率限制：IP+账号 与 单 IP 双层
        // 单层只按 IP 会被共享出口(NAT/公司网络)误伤，攻击者换代理 IP 也能绕过
        RateLimiter::for('login', static function (Request $request) {
            $ip = $request->ip();
            $limits = [Limit::perMinute(config('custom.rate_limits.login_ip'))->by('ip:'.$ip)];

            // 中间件先于表单验证执行，此处必须自己确认是字符串（username 可能被传成数组）
            $account = $request->input('username');

            // 小程序登录只有一次性 code，取不到账号标识，仅受单 IP 限制
            if (is_string($account) && $account !== '') {
                // 账号维度带上 IP，避免攻击者用错误密码锁死他人账号
                $limits[] = Limit::perMinute(config('custom.rate_limits.login'))->by('account:'.$ip.'|'.$account);
            }

            return $limits;
        });

        // 短信发送频率限制：IP+手机号 与 单 IP 双层
        RateLimiter::for('sms', static function (Request $request) {
            $ip = $request->ip();
            $limits = [Limit::perMinute(config('custom.rate_limits.sms_ip'))->by('ip:'.$ip)];

            $mobile = $request->input('mobile');

            if (is_string($mobile) && $mobile !== '') {
                $limits[] = Limit::perMinute(config('custom.rate_limits.sms'))->by('mobile:'.$ip.'|'.$mobile);
            }

            return $limits;
        });

        // 用户注册频率限制
        RateLimiter::for('register', static function (Request $request) {
            return Limit::perMinute(config('custom.rate_limits.register'))
                ->by($request->ip());
        });

        // 租户令牌频率限制（机器对机器，按 app_key 区分调用方）
        RateLimiter::for('tenant', static function (Request $request) {
            $appKey = $request->input('app_key');

            return Limit::perMinute(config('custom.rate_limits.tenant'))
                ->by(is_string($appKey) && $appKey !== '' ? $appKey : $request->ip());
        });
    }

    protected function bootBluePrint(): void
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

    protected function bootSettlementTasks(): void
    {
        TaskService::registerMany([
            DirectReward::class,
            SecondReward::class,
        ]);
    }
}
