<?php

namespace App\Extensions\BlockChain\Rpc;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RpcClient
{
    private int $requestId = 1;

    public function __construct(
        private readonly string $rpcUrl,
        private readonly int $timeout = 30,
    ) {}

    /**
     * 发送 JSON-RPC 2.0 请求并返回结果
     *
     * @param  string  $method  RPC 方法名
     * @param  array  $params  方法参数
     * @return mixed  响应中解码后的 'result' 字段
     *
     * @throws RuntimeException|ConnectionException  HTTP/网络错误或 JSON-RPC 错误响应
     */
    public function send(string $method, array $params = []): mixed
    {
        $response = Http::timeout($this->timeout)
            ->retry(2, 1000)
            ->post($this->rpcUrl, [
                'jsonrpc' => '2.0',
                'id' => $this->requestId++,
                'method' => $method,
                'params' => $params,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'RPC HTTP error: %d %s',
                $response->status(),
                $response->body()
            ));
        }

        $data = $response->json();

        if (isset($data['error'])) {
            throw new RuntimeException(sprintf(
                'RPC error [%s]: %s',
                $data['error']['code'] ?? 'unknown',
                $data['error']['message'] ?? 'unknown error'
            ));
        }

        return $data['result'] ?? null;
    }

    /**
     * 向非标准 JSON-RPC 端点发送原始 POST 请求（如 TRON）
     *
     * @param  string  $path    URL 路径（如 /wallet/deploycontract）
     * @param  array   $payload  关联数组形式的请求体
     * @return array   解析后的 JSON 响应
     *
     * @throws RuntimeException|ConnectionException
     */
    public function postRaw(string $path, array $payload): array
    {
        $url = rtrim($this->rpcUrl, '/').'/'.ltrim($path, '/');

        $response = Http::timeout($this->timeout)
            ->retry(2, 1000)
            ->post($url, $payload);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'HTTP error: %d %s',
                $response->status(),
                $response->body()
            ));
        }

        return $response->json() ?: [];
    }
}
