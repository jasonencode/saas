<?php

use App\Http\Middleware\AddDebugInfoMiddleware;
use App\Http\Middleware\BlackIpList;
use App\Http\Middleware\GuessAuthenticate;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //$middleware->trustProxies(at: []);
        $middleware->alias([
            'guess' => GuessAuthenticate::class,
        ]);

        $middleware->append([
            AddDebugInfoMiddleware::class,
        ]);

        $middleware->api([
            BlackIpList::class,
            'throttle:api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        if (request()->is('api/*')) {
            $exceptions->render(function(AuthenticationException $e) {
                return Response::json(['message' => $e->getMessage()], 401);
            });
            $exceptions->render(function(AccessDeniedHttpException $e) {
                return Response::json(['message' => $e->getMessage()], 403);
            });
            $exceptions->render(function(NotFoundHttpException $e) {
                return Response::json(['message' => $e->getMessage()], 404);
            });
            $exceptions->render(function(TooManyRequestsHttpException $e) {
                return Response::json(['message' => $e->getMessage()], 429);
            });
            $exceptions->render(function(ValidationException $e) {
                return Response::json(['message' => $e->getMessage()], 422);
            });
            $exceptions->render(function(Exception $e) {
                return Response::json(['message' => $e->getMessage()], 500);
            });
        }
    })->create();
