<?php

namespace App\Http\Handlers;

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class ApiExceptionHandler
{
    /**
     * 处理API异常并返回统一格式的JSON响应
     */
    public static function handle(Throwable $exception, Request $request): JsonResponse
    {
        return match (true) {
            $exception instanceof ValidationException => self::handleValidationException($exception),
            $exception instanceof AuthenticationException => self::handleAuthenticationException($exception),
            $exception instanceof AccessDeniedHttpException => self::handleAccessDeniedException($exception),
            $exception instanceof NotFoundHttpException,
                $exception instanceof ModelNotFoundException => self::handleNotFoundException($exception),
            $exception instanceof TooManyRequestsHttpException => self::handleTooManyRequestsException($exception),
            $exception instanceof HttpException => self::handleHttpException($exception),
            default => self::handleGenericException($exception),
        };
    }

    /**
     * 处理验证异常
     */
    private static function handleValidationException(ValidationException $exception): JsonResponse
    {
        return ApiResponse::validationError($exception->errors());
    }

    /**
     * 处理认证异常
     */
    private static function handleAuthenticationException(AuthenticationException $exception): JsonResponse
    {
        return ApiResponse::unauthorized();
    }

    /**
     * 处理权限异常
     */
    private static function handleAccessDeniedException(AccessDeniedHttpException $exception): JsonResponse
    {
        return ApiResponse::forbidden();
    }

    /**
     * 处理404异常
     */
    private static function handleNotFoundException(Throwable $exception): JsonResponse
    {
        $message = $exception instanceof ModelNotFoundException
            ? '请求的资源不存在'
            : '请求的接口不存在';

        return ApiResponse::notFound($message);
    }

    /**
     * 处理限流异常
     */
    private static function handleTooManyRequestsException(TooManyRequestsHttpException $exception): JsonResponse
    {
        $response = [
            'success' => false,
            'error_code' => 'TOO_MANY_REQUESTS',
            'message' => '请求过于频繁，请稍后再试',
        ];

        if (isset($exception->getHeaders()['Retry-After'])) {
            $response['retry_after'] = $exception->getHeaders()['Retry-After'];
        }

        return response()->json($response, Response::HTTP_TOO_MANY_REQUESTS);
    }

    /**
     * 处理HTTP异常
     */
    private static function handleHttpException(HttpException $exception): JsonResponse
    {
        return ApiResponse::error(
            $exception->getMessage() ?: 'HTTP错误',
            'HTTP_ERROR',
            null,
            $exception->getStatusCode()
        );
    }

    /**
     * 处理通用异常
     */
    private static function handleGenericException(Throwable $exception): JsonResponse
    {
        $message = app()->environment('production')
            ? '服务器内部错误，请稍后重试'
            : $exception->getMessage();

        $errors = null;

        // 开发环境下添加调试信息
        if (!app()->environment('production')) {
            $errors = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        return ApiResponse::error($message, 'INTERNAL_ERROR', $errors, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
