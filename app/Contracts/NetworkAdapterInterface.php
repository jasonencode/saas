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
     * 生成 secp256k1 私钥
     *
     * @return string 生成的私钥
     */
    public function generatePrivateKey(): string;

    /**
     * 验证私钥是否合法
     *
     * @param  string  $privateKey  私钥
     *
     * @return bool 是否合法
     */
    public function validatePrivateKey(string $privateKey): bool;

    /**
     * 从私钥导出未压缩公钥
     *
     * @param  string  $privateKey  私钥
     *
     * @return string 未压缩公钥
     */
    public function getPublicKeyFromPrivateKey(string $privateKey): string;

    /**
     * 从公钥派生区块链地址
     *
     * @param  string  $publicKey  公钥
     *
     * @return string 区块链地址
     */
    public function getAddressFromPublicKey(string $publicKey): string;

    /**
     * 从私钥派生区块链地址
     *
     * @param  string  $privateKey  私钥
     *
     * @return string 区块链地址
     */
    public function getAddressFromPrivateKey(string $privateKey): string;

    /**
     * 查询节点当前区块高度
     *
     * @param  string  $rpcUrl  节点 RPC 地址
     * @param  array  $sslOptions  SSL 选项
     * @param  string|null  $groupId  群组 ID
     *
     * @throws RuntimeException 节点通讯失败
     * @throws ConnectionException 连接异常
     *
     * @return int 当前区块高度
     */
    public function getBlockNumber(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): int;

    /**
     * 部署智能合约
     *
     * @param  string  $privateKey  部署者私钥
     * @param  string  $bytecode  合约字节码
     * @param  string|null  $abi  合约 ABI
     * @param  array<int, mixed>  $constructorArgs  构造函数参数
     * @param  string  $rpcUrl  节点 RPC 地址
     * @param  array  $sslOptions  SSL 选项
     *
     * @throws RuntimeException 节点通讯失败
     * @throws ConnectionException 连接异常
     * @throws JsonException JSON 序列化失败
     *
     * @return array{contract_address: string, tx_hash: string} 合约地址与交易哈希
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
     * 获取节点 peer 列表
     *
     * @param  string  $rpcUrl  节点 RPC 地址
     * @param  array  $sslOptions  SSL 选项
     * @param  string|null  $groupId  群组 ID
     *
     * @throws RuntimeException 节点通讯失败
     * @throws ConnectionException 连接异常
     *
     * @return array peer 列表
     */
    public function getPeers(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array;

    /**
     * 获取节点同步状态
     *
     * @param  string  $rpcUrl  节点 RPC 地址
     * @param  array  $sslOptions  SSL 选项
     * @param  string|null  $groupId  群组 ID
     *
     * @throws RuntimeException 节点通讯失败
     * @throws ConnectionException 连接异常
     *
     * @return array 同步状态信息
     */
    public function getSyncStatus(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array;
}
