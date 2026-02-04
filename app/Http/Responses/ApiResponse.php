<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * 成功响应
     */
    public static function success(
        mixed $data = null,
        string $message = '操作成功',
        int $code = Response::HTTP_OK
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * 失败响应
     */
    public static function error(
        string $message = '操作失败',
        string $errorCode = 'ERROR',
        mixed $errors = null,
        int $code = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        $response = [
            'success' => false,
            'error_code' => $errorCode,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * 分页响应
     */
    public static function paginated(
        $paginator,
        string $message = '获取成功'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * 创建响应
     */
    public static function created(
        mixed $data = null,
        string $message = '创建成功'
    ): JsonResponse {
        return self::success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * 无内容响应
     */
    public static function noContent(string $message = '操作成功'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * 验证失败响应
     */
    public static function validationError(
        array $errors,
        string $message = '请求参数验证失败'
    ): JsonResponse {
        return self::error($message, 'VALIDATION_ERROR', $errors, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * 未授权响应
     */
    public static function unauthorized(string $message = '认证失败，请重新登录'): JsonResponse
    {
        return self::error($message, 'AUTHENTICATION_ERROR', null, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * 权限不足响应
     */
    public static function forbidden(string $message = '权限不足，无法访问该资源'): JsonResponse
    {
        return self::error($message, 'ACCESS_DENIED', null, Response::HTTP_FORBIDDEN);
    }

    /**
     * 资源不存在响应
     */
    public static function notFound(string $message = '请求的资源不存在'): JsonResponse
    {
        return self::error($message, 'NOT_FOUND', null, Response::HTTP_NOT_FOUND);
    }

    /**
     * 服务器错误响应
     */
    public static function serverError(string $message = '服务器内部错误'): JsonResponse
    {
        return self::error($message, 'INTERNAL_ERROR', null, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
