<?php

namespace App\Extensions\BlockChain\Rpc;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RpcClient
{
    const string RPC_REQUEST_ID = 'rpc_request_id';

    public function __construct(
        private readonly string $rpcUrl,
        private readonly int $timeout = 30,
        private readonly array $sslOptions = [],
    ) {}

    /**
     * 发送 JSON-RPC 2.0 请求并返回 result 字段。
     *
     * @throws RuntimeException|ConnectionException
     */
    public function send(string $method, array $params = []): mixed
    {
        $requestId = Cache::increment(self::RPC_REQUEST_ID);

        $response = $this->buildHttpClient()
            ->post($this->rpcUrl, [
                'jsonrpc' => '2.0',
                'id' => $requestId,
                'method' => $method,
                'params' => $params,
            ]);

        return $this->handleResponse($response, false, $requestId);
    }

    /**
     * 向非标准 JSON-RPC 端点发送原始 POST 请求。
     *
     * @throws RuntimeException|ConnectionException
     */
    public function postRaw(string $path, array $payload): array
    {
        $url = rtrim($this->rpcUrl, '/').'/'.ltrim($path, '/');

        $response = $this->buildHttpClient()
            ->post($url, $payload);

        $result = $this->handleResponse($response, true);

        if (! is_array($result)) {
            throw new RuntimeException(sprintf(
                'Expected array response from %s, got %s',
                $path,
                get_debug_type($result)
            ));
        }

        return $result;
    }

    private function buildHttpClient(): PendingRequest
    {
        $client = Http::timeout($this->timeout)
            ->retry(2, 1000);

        $shouldVerify = true;

        if (! empty($this->sslOptions)) {
            $shouldVerify = $this->sslOptions['verify'] ?? true;
            $transportOptions = array_diff_key($this->sslOptions, ['verify' => true]);

            if (! empty($transportOptions)) {
                $client = $client->withOptions($transportOptions);
            }
        }

        return $shouldVerify ? $client : $client->withoutVerifying();
    }

    private function handleResponse(Response $response, bool $rawMode = false, ?int $requestId = null): mixed
    {
        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'HTTP error: %d %s',
                $response->status(),
                $response->body()
            ));
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException(sprintf(
                'RPC response is not valid JSON: %s',
                mb_substr($response->body(), 0, 500)
            ));
        }

        if ($requestId !== null && isset($data['id']) && $data['id'] !== $requestId) {
            throw new RuntimeException(sprintf(
                'RPC id mismatch: sent %d, received %d',
                $requestId,
                $data['id']
            ));
        }

        if (isset($data['error'])) {
            throw new RuntimeException(sprintf(
                'RPC error [%s]: %s',
                $data['error']['code'] ?? 'unknown',
                $data['error']['message'] ?? 'unknown error'
            ));
        }

        return $rawMode ? $data : ($data['result'] ?? null);
    }
}
