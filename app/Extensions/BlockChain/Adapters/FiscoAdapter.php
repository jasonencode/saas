<?php

namespace App\Extensions\BlockChain\Adapters;

use App\Extensions\BlockChain\Rpc\RpcClient;
use Illuminate\Http\Client\ConnectionException;

class FiscoAdapter extends AbstractEvmAdapterInterface
{
    /**
     * FISCO BCOS 的 JSON-RPC 要求将 groupID 作为 params 的第一个参数传入。
     */
    public function getBlockNumber(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): int
    {
        $groupId ??= 1;

        $rpc = new RpcClient($rpcUrl, 30, $sslOptions);

        return (int) hexdec($rpc->send('getBlockNumber', [$groupId]));
    }

    /**
     * 获取节点 peer 列表
     *
     * @param  string  $rpcUrl
     * @param  array  $sslOptions
     * @param  string|null  $groupId
     * @return array  peer 节点信息列表
     * @throws ConnectionException
     */
    public function getPeers(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array
    {
        $groupId ??= 1;

        $rpc = new RpcClient($rpcUrl, 30, $sslOptions);

        return $rpc->send('getPeers', [$groupId]) ?? [];
    }

    /**
     * 获取节点同步状态
     *
     * @param  string  $rpcUrl
     * @param  array  $sslOptions
     * @param  string|null  $groupId
     * @return array  同步状态信息（blockNumber, txPoolSize 等）
     * @throws ConnectionException
     */
    public function getSyncStatus(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array
    {
        $rpc = new RpcClient($rpcUrl, 30, $sslOptions);

        return json_decode($rpc->send('getSyncStatus', [$groupId]), true, 512, JSON_THROW_ON_ERROR) ?? [];
    }

    /**
     * FISCO BCOS 地址通过 SHA3-256 派生（非 Keccak-256）
     * 覆盖以使用 FISCO 特定的地址派生方式
     */
    public function getAddressFromPublicKey(string $publicKey): string
    {
        return $this->fiscoAddressFromPublicKey($publicKey);
    }

    /* deployContract() 继承自 AbstractEvmAdapterInterface —
     * FISCO BCOS 使用 EVM 兼容的 JSON-RPC（eth_sendRawTransaction、
     * eth_getTransactionReceipt）和相同的 ECDSA secp256k1 签名，
     * 交易哈希使用 Keccak-256。仅地址派生不同（地址使用 SHA3-256，
     * 由 fiscoAddressFromPublicKey 处理）。
     */
}
