<?php

namespace App\Contracts;

use Illuminate\Http\Client\ConnectionException;
use JsonException;
use RuntimeException;

/**
 * 区块链网络适配器接口
 *
 * 定义所有区块链适配器必须实现的方法，涵盖密钥管理、地址派生和节点通讯。
 */
interface NetworkAdapterInterface
{
    /**
     * 生成 secp256k1 私钥。
     */
    public function generatePrivateKey(): string;

    /**
     * 验证私钥是否合法。
     */
    public function validatePrivateKey(string $privateKey): bool;

    /**
     * 从私钥导出未压缩公钥。
     */
    public function getPublicKeyFromPrivateKey(string $privateKey): string;

    /**
     * 从公钥派生区块链地址。
     */
    public function getAddressFromPublicKey(string $publicKey): string;

    /**
     * 从私钥派生区块链地址。
     */
    public function getAddressFromPrivateKey(string $privateKey): string;

    /**
     * 查询节点当前区块高度。
     *
     * @throws RuntimeException
     * @throws ConnectionException
     */
    public function getBlockNumber(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): int;

    /**
     * 部署智能合约。
     *
     * @param  array<int, mixed>  $constructorArgs
     * @return array{contract_address: string, tx_hash: string}
     *
     * @throws RuntimeException
     * @throws ConnectionException
     * @throws JsonException
     */
    public function deployContract(
        string $privateKey,
        string $bytecode,
        ?string $abi = null,
        array $constructorArgs = [],
        string $rpcUrl = '',
        array $sslOptions = [],
    ): array;

    /**
     * 获取节点 peer 列表。
     *
     * @throws RuntimeException
     * @throws ConnectionException
     */
    public function getPeers(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array;

    /**
     * 获取节点同步状态。
     *
     * @throws RuntimeException
     * @throws ConnectionException
     */
    public function getSyncStatus(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array;
}
