<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * 成功响应
     * 单个资源直接返回数据，不包裹 data 层
     */
    public static function success(
        mixed $data = null,
        string $message = '操作成功',
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        // 如果只有消息没有数据，返回简单成功响应
        if ($data === null) {
            $data = [
                'code' => 0,
                'message' => $message,
            ];
        }

        // 单个资源或数组直接返回，不包裹 data 层
        return response()->json($data, $statusCode);
    }

    /**
     * 失败响应
     */
    public static function error(
        string $message = '操作失败',
        int $code = 1,
        mixed $errors = null,
        int $statusCode = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        $response = [
            'code' => $code,
            'message' => $message,
        ];

        // 验证错误直接平铺在响应中
        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
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
            'code' => 0,
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
        return self::error(
            message: $message,
            code: 429,
            errors: $errors,
            statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    /**
     * 未授权响应
     */
    public static function unauthorized(string $message = '认证失败，请重新登录'): JsonResponse
    {
        return self::error(
            message: $message,
            code: 401,
            statusCode: Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * 权限不足响应
     */
    public static function forbidden(string $message = '权限不足，无法访问该资源'): JsonResponse
    {
        return self::error(
            message: $message,
            code: 403,
            statusCode: Response::HTTP_FORBIDDEN
        );
    }

    /**
     * 资源不存在响应
     */
    public static function notFound(string $message = '请求的资源不存在'): JsonResponse
    {
        return self::error(
            message: $message,
            code: 404,
            statusCode: Response::HTTP_NOT_FOUND
        );
    }

    /**
     * 服务器错误响应
     */
    public static function serverError(string $message = '服务器内部错误'): JsonResponse
    {
        return self::error(
            message: $message,
            code: 500,
            statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
