<?php

namespace App\Http\Middleware;

use App\Models\System\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use JsonException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiRecord
{
    /**
     * 需要脱敏的字段名（小写）
     *
     * @var array<int, string>
     */
    private const array SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'old_password',
        'payment_password',
        'app_secret',
        'secret',
        'private_key',
        'access_token',
        'refresh_token',
        'token',
        'signature',
        'authorization',
    ];

    /**
     * 脱敏占位符
     */
    private const string MASK = '***';

    /**
     * 处理请求，记录API调用日志
     *
     * @param  Request  $request  当前请求
     * @param  Closure  $next  下一步处理
     *
     * @return Response 响应对象
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        try {
            $duration = (int) round((microtime(true) - $start) * 1000);

            $input = $this->parseInput($request);

            $output = method_exists($response, 'getContent') ? $this->parseOutput($response->getContent()) : null;
            if (is_string($output) && strlen($output) > 1000) {
                $output = substr($output, 0, 1000);
            }

            ApiLog::create([
                'user_type' => Auth::user() ? Auth::user()->getMorphClass() : null,
                'user_id' => Auth::id(),
                'method' => $request->getMethod(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status_code' => $response->getStatusCode(),
                'duration' => $duration,
                'input' => $input,
                'output' => $output,
            ]);
        } catch (Throwable $e) {
            report($e);
        }

        return $response;
    }

    /**
     * 解析请求输入数据（已脱敏）
     *
     * @param  Request  $request  当前请求
     *
     * @throws JsonException
     *
     * @return string 解析后的输入数据
     */
    private function parseInput(Request $request): string
    {
        return $this->encode($this->mask($request->all()));
    }

    /**
     * 解析响应输出数据（已脱敏）
     *
     * @param  mixed  $output  响应内容
     *
     * @throws JsonException
     *
     * @return string|null 解析后的输出数据
     */
    private function parseOutput(mixed $output): ?string
    {
        if (!is_string($output)) {
            return null;
        }

        $decoded = json_decode($output, true);

        return is_array($decoded) ? $this->encode($this->mask($decoded)) : $output;
    }

    /**
     * 递归脱敏敏感字段
     *
     * @param  array<string, mixed>  $data  原始数据
     *
     * @return array<string, mixed> 脱敏后的数据
     */
    private function mask(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->mask($value);

                continue;
            }

            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true) && $value !== null && $value !== '') {
                $data[$key] = self::MASK;
            }
        }

        return $data;
    }

    /**
     * 编码为 JSON 字符串
     *
     * @param  array<string, mixed>  $data  数据
     *
     * @throws JsonException
     *
     * @return string JSON 字符串
     */
    private function encode(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
