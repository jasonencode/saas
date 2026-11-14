<?php

namespace App\Http\Handlers;

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PDOException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class ApiExceptionHandler
{
    /**
     * 处理API异常并返回统一格式的JSON响应
     *
     * @param  Throwable  $exception  异常实例
     * @param  Request  $request  当前请求
     *
     * @return JsonResponse 统一格式的异常响应
     */
    public static function handle(Throwable $exception, Request $request): JsonResponse
    {
        return match (true) {
            $exception instanceof ValidationException => self::handleValidationException($exception),
            $exception instanceof AuthenticationException => self::handleAuthenticationException($exception, $request),
            $exception instanceof AccessDeniedHttpException => self::handleAccessDeniedException($exception, $request),
            $exception instanceof NotFoundHttpException,
            $exception instanceof ModelNotFoundException => self::handleNotFoundException($exception),
            $exception instanceof TooManyRequestsHttpException => self::handleTooManyRequestsException($exception, $request),
            // HttpException 家族全继承自 RuntimeException，必须先于业务异常分支判定
            $exception instanceof HttpException => self::handleHttpException($exception),
            // 数据库异常继承自 RuntimeException，按 500 处理，避免把 SQL 细节透给调用方
            $exception instanceof PDOException => self::handleGenericException($exception),
            // 业务异常：可预期的用户操作错误（券不可用、库存不足、履约方式不支持、
            // 地址不正确等），返回 400 并透出原始提示
            $exception instanceof InvalidArgumentException,
            $exception instanceof RuntimeException => self::handleBusinessException($exception, $request),
            default => self::handleGenericException($exception),
        };
    }

    /**
     * 是否按 API 统一格式渲染异常
     *
     * @param  Request  $request  当前请求
     *
     * @return bool 是否为 API 请求
     */
    public static function shouldRenderAsApi(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    /**
     * 是否属于可预期的业务错误
     *
     * 仅当异常由应用代码主动抛出（文件位于 app/），且属于 RuntimeException /
     * InvalidArgumentException 时成立：这类异常会渲染为 400 + 原始提示，
     * 不应再按 5xx 记录 ERROR 堆栈。HttpException（401/403/429 等）与
     * PDOException（含 QueryException）不属于业务错误。
     *
     * @param  Throwable  $exception  异常
     *
     * @return bool 是否为业务错误
     */
    public static function isBusinessError(Throwable $exception): bool
    {
        $request = app()->bound('request') ? request() : null;

        if (!$request instanceof Request || !self::shouldRenderAsApi($request)) {
            return false;
        }

        if ($exception instanceof HttpException || $exception instanceof PDOException) {
            return false;
        }

        if (!$exception instanceof InvalidArgumentException && !$exception instanceof RuntimeException) {
            return false;
        }

        // 只认应用代码抛出的异常，避免把第三方库的 RuntimeException（如 HTTP 客户端超时）一并降级
        return Str::startsWith($exception->getFile(), base_path('app'));
    }

    /**
     * 处理业务异常
     *
     * 覆盖 InvalidArgumentException 与 RuntimeException 两类可预期的业务错误
     * （如券不可用、库存不足、履约方式不支持、地址不正确等），返回 400 并保留原始提示。
     *
     * @param  InvalidArgumentException|RuntimeException  $exception  业务异常
     * @param  Request  $request  当前请求
     *
     * @return JsonResponse 400 业务错误响应
     */
    private static function handleBusinessException(InvalidArgumentException|RuntimeException $exception, Request $request): JsonResponse
    {
        self::logBusiness($exception, $request);

        return ApiResponse::error($exception->getMessage() ?: '请求处理失败');
    }

    /**
     * 记录业务异常（warning，保留 request_id 等共享上下文）
     *
     * @param  Throwable  $exception  异常
     * @param  Request  $request  当前请求
     */
    private static function logBusiness(Throwable $exception, Request $request): void
    {
        try {
            Log::warning('业务异常：'.$exception->getMessage(), [
                'category' => 'business',
                'exception' => $exception::class,
                'method' => $request->method(),
                'path' => $request->path(),
                'user_id' => $request->user()?->getAuthIdentifier(),
                'ip' => $request->ip(),
            ]);
        } catch (Throwable) {
            // 日志写入失败（磁盘满 / 通道配置错误）不能影响异常响应
        }
    }

    /**
     * 记录安全事件（warning，便于告警与审计检索）
     *
     * @param  string  $event  事件标识（authentication_failed / access_denied / rate_limited）
     * @param  Throwable  $exception  异常
     * @param  Request  $request  当前请求
     */
    private static function logSecurity(string $event, Throwable $exception, Request $request): void
    {
        try {
            Log::warning('安全事件：'.$event, [
                'category' => 'security',
                'event' => $event,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'method' => $request->method(),
                'path' => $request->path(),
                'user_id' => $request->user()?->getAuthIdentifier(),
                'ip' => $request->ip(),
            ]);
        } catch (Throwable) {
            // 日志写入失败不能影响异常响应
        }
    }

    /**
     * 处理验证异常
     *
     * @param  ValidationException  $exception  验证异常
     *
     * @return JsonResponse 验证失败响应
     */
    private static function handleValidationException(ValidationException $exception): JsonResponse
    {
        return ApiResponse::validationError($exception->errors());
    }

    /**
     * 处理认证异常
     *
     * @param  AuthenticationException  $exception  认证异常
     * @param  Request  $request  当前请求
     *
     * @return JsonResponse 未授权响应
     */
    private static function handleAuthenticationException(AuthenticationException $exception, Request $request): JsonResponse
    {
        self::logSecurity('authentication_failed', $exception, $request);

        return ApiResponse::unauthorized();
    }

    /**
     * 处理权限异常
     *
     * @param  AccessDeniedHttpException  $exception  权限异常
     * @param  Request  $request  当前请求
     *
     * @return JsonResponse 权限不足响应
     */
    private static function handleAccessDeniedException(AccessDeniedHttpException $exception, Request $request): JsonResponse
    {
        self::logSecurity('access_denied', $exception, $request);

        return ApiResponse::forbidden();
    }

    /**
     * 处理404异常
     *
     * @param  Throwable  $exception  404异常
     *
     * @return JsonResponse 资源不存在响应
     */
    private static function handleNotFoundException(Throwable $exception): JsonResponse
    {
        $message = $exception instanceof ModelNotFoundException
            || $exception->getPrevious() instanceof ModelNotFoundException
            ? '请求的资源不存在'
            : '请求的接口不存在';

        return ApiResponse::notFound($message);
    }

    /**
     * 处理限流异常
     *
     * @param  TooManyRequestsHttpException  $exception  限流异常
     * @param  Request  $request  当前请求
     *
     * @return JsonResponse 请求过于频繁响应
     */
    private static function handleTooManyRequestsException(TooManyRequestsHttpException $exception, Request $request): JsonResponse
    {
        self::logSecurity('rate_limited', $exception, $request);

        $response = null;

        if (isset($exception->getHeaders()['Retry-After'])) {
            $response['retry_after'] = $exception->getHeaders()['Retry-After'];
        }

        return ApiResponse::error(
            message: '请求过于频繁，请稍后再试',
            code: $exception->getStatusCode(),
            errors: $response,
            statusCode: $exception->getStatusCode(),
        );
    }

    /**
     * 处理HTTP异常
     *
     * @param  HttpException  $exception  HTTP异常
     *
     * @return JsonResponse HTTP错误响应
     */
    private static function handleHttpException(HttpException $exception): JsonResponse
    {
        return ApiResponse::error(
            $exception->getMessage() ?: 'HTTP错误',
            $exception->getStatusCode(),
            null,
            $exception->getStatusCode()
        );
    }

    /**
     * 处理通用异常
     *
     * @param  Throwable  $exception  通用异常
     *
     * @return JsonResponse 服务器错误响应
     */
    private static function handleGenericException(Throwable $exception): JsonResponse
    {
        $message = app()->environment('production')
            ? '服务器内部错误，请稍后重试'
            : $exception->getMessage();

        return ApiResponse::serverError($message);
    }
}
