<?php

namespace App\Http\Middleware;

use App\Models\System\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiRecord
{
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

            $output = method_exists($response, 'getContent') ? $response->getContent() : null;
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
        } catch (Throwable) {
        }

        return $response;
    }

    /**
     * 解析请求输入数据
     *
     * @param  Request  $request  当前请求
     *
     * @return string 解析后的输入数据
     */
    private function parseInput(Request $request): string
    {
        if ($request->isMethod('GET')) {
            return json_encode($request->all(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        return $request->getContent();
    }
}
