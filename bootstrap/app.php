<?php

use App\Http\Handlers\ApiExceptionHandler;
use App\Http\Middleware\AddDebugInfoMiddleware;
use App\Http\Middleware\GuessAuthenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
        ],
        api: [
            __DIR__.'/../routes/api.php',
            __DIR__.'/../routes/api/auth.php',
            __DIR__.'/../routes/api/chain.php',
            __DIR__.'/../routes/api/content.php',
            __DIR__.'/../routes/api/mall.php',
            __DIR__.'/../routes/api/redpack.php',
            __DIR__.'/../routes/api/user.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        # 信任代理
        // $middleware->trustProxies(at: '*');
        $middleware->alias([
            'guess' => GuessAuthenticate::class,
        ]);
        $middleware->append([
            # 对头信息，增加server-id，方便调试用的
            AddDebugInfoMiddleware::class,
        ]);
        $middleware->api([
            // BlackIpList::class,
            'throttle:api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API异常处理
        $exceptions->render(function (\Throwable $exception, $request) {
            if ($request->is('api/*')) {
                return ApiExceptionHandler::handle($exception, $request);
            }
        });
    })->create();
