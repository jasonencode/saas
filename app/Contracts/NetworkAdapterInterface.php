<?php

namespace App\Contracts;

interface NetworkAdapterInterface
{
    public function generatePrivateKey(): string;

    public function validatePrivateKey(string $privateKey): bool;

    public function getPublicKeyFromPrivateKey(string $privateKey): string;

    public function getAddressFromPublicKey(string $publicKey): string;

    public function getAddressFromPrivateKey(string $privateKey): string;

    public function getBlockNumber(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): int;

    /**
     * 获取节点 peer 列表
     *
     * @return array  peer 信息列表
     */
    public function getPeers(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array;

    /**
     * 获取节点同步状态
     *
     * @return array  同步状态信息
     */
    public function getSyncStatus(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array;

}
