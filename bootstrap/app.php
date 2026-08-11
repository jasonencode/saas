<?php

use App\Http\Handlers\ApiExceptionHandler;
use App\Http\Middleware\AddDebugInfoMiddleware;
use App\Http\Middleware\EnsureStoreIsOpened;
use App\Http\Middleware\GuessAuthenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
        ],
        api: [
            __DIR__.'/../routes/api.php',
            __DIR__.'/../routes/apis/auth.php',
            __DIR__.'/../routes/apis/chain.php',
            __DIR__.'/../routes/apis/content.php',
            __DIR__.'/../routes/apis/mall.php',
            __DIR__.'/../routes/apis/campaign.php',
            __DIR__.'/../routes/apis/user.php',
            __DIR__.'/../routes/apis/finance.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 信任代理
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'guess' => GuessAuthenticate::class,
            'store.opened' => EnsureStoreIsOpened::class,
        ]);
        $middleware->append([
            // 对头信息，增加server-id，方便调试用的
            AddDebugInfoMiddleware::class,
        ]);
        $middleware->api([
            // BlackIpList::class,
            'throttle:api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        // API异常处理
        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($request->is('api/*')) {
                return ApiExceptionHandler::handle($exception, $request);
            }

            return false;
        });
    })->create();
