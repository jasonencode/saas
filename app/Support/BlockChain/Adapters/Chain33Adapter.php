<?php

namespace App\Support\BlockChain\Adapters;

use App\Contracts\NetworkAdapterInterface;
use App\Support\BlockChain\Abi\AbiEncoder;
use App\Support\BlockChain\Adapters\Traits\Secp256k1KeyOps;
use App\Support\BlockChain\Rpc\RpcClient;
use Elliptic\EC;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use JsonException;
use RuntimeException;

class Chain33Adapter implements NetworkAdapterInterface
{
    use Secp256k1KeyOps;

    /**
     * 获取当前区块高度
     *
     * @param  string  $rpcUrl  RPC 地址
     * @param  array  $sslOptions  SSL 选项
     * @param  string|null  $groupId  组 ID
     *
     * @throws RuntimeException|ConnectionException 连接失败
     *
     * @return int 区块高度
     */
    public function getBlockNumber(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): int
    {
        $rpc = new RpcClient($rpcUrl, 30);

        $header = $rpc->send('Chain33.GetLastHeader');

        if (is_array($header) && isset($header['height'])) {
            return (int) $header['height'];
        }

        throw new RuntimeException('Chain33.GetLastHeader: unexpected response, missing height');
    }

    /**
     * 获取节点列表
     *
     * @param  string  $rpcUrl  RPC 地址
     * @param  array  $sslOptions  SSL 选项
     * @param  string|null  $groupId  组 ID
     *
     * @throws RuntimeException|ConnectionException 连接失败
     *
     * @return array<int|string, mixed> 节点列表
     */
    public function getPeers(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array
    {
        $rpc = new RpcClient($rpcUrl, 30);

        return $rpc->send('Chain33.GetPeerInfo')['peers'] ?? [];
    }

    /**
     * 获取同步状态
     *
     * @param  string  $rpcUrl  RPC 地址
     * @param  array  $sslOptions  SSL 选项
     * @param  string|null  $groupId  组 ID
     *
     * @throws RuntimeException|ConnectionException 连接失败
     *
     * @return array{isSync: bool, netInfo: array<int|string, mixed>} 同步状态信息
     */
    public function getSyncStatus(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array
    {
        $rpc = new RpcClient($rpcUrl, 30);

        return [
            'isSync' => (bool) $rpc->send('Chain33.IsSync'),
            'netInfo' => $rpc->send('Chain33.GetNetInfo') ?? [],
        ];
    }

    /**
     * 从私钥获取地址
     *
     * @param  string  $privateKey  私钥
     *
     * @return string 地址
     */
    public function getAddressFromPrivateKey(string $privateKey): string
    {
        return $this->getAddressFromPublicKey($this->getCompressedPublicKeyFromPrivateKey($privateKey));
    }

    /**
     * 从公钥获取地址
     *
     * @param  string  $publicKey  公钥
     *
     * @return string 地址
     */
    public function getAddressFromPublicKey(string $publicKey): string
    {
        if (str_starts_with($publicKey, '0x')) {
            $publicKey = substr($publicKey, 2);
        }

        if (str_starts_with($publicKey, '04')) {
            $publicKey = self::compressPublicKey($publicKey);
        }

        $sha256 = hash('sha256', hex2bin($publicKey));
        $ripemd160 = hash('ripemd160', hex2bin($sha256));

        $withPrefix = '00'.$ripemd160;
        $checksum = hash('sha256', hex2bin(hash('sha256', hex2bin($withPrefix))));
        $addressHex = $withPrefix.substr($checksum, 0, 8);

        return self::chain33Base58Encode($addressHex);
    }

    /**
     * 部署合约
     *
     * @param  string  $privateKey  私钥
     * @param  string  $bytecode  合约字节码
     * @param  string|null  $abi  ABI JSON
     * @param  array<int, mixed>  $constructorArgs  构造函数参数
     * @param  string  $rpcUrl  RPC 地址
     * @param  array  $sslOptions  SSL 选项
     *
     * @throws RuntimeException|JsonException|ConnectionException 部署失败
     *
     * @return array{contract_address: string, tx_hash: string} 部署结果
     */
    public function deployContract(
        string $privateKey,
        string $bytecode,
        ?string $abi = null,
        array $constructorArgs = [],
        string $rpcUrl = '',
        array $sslOptions = [],
    ): array {
        $rpc = new RpcClient($rpcUrl, 60);

        $encodedArgs = AbiEncoder::encodeConstructor($abi ?? '', $constructorArgs);
        $deployData = self::strip0x($bytecode).$encodedArgs;

        $payload = [
            'code' => '0x'.$deployData,
            'abi' => $abi ?? '',
            'parameter' => $encodedArgs ? '0x'.$encodedArgs : '',
        ];

        $unsignedTx = $rpc->send('Chain33.CreateTransaction', [
            [
                'execer' => 'evm',
                'actionName' => 'deploy',
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            ],
        ]);

        if (!is_string($unsignedTx)) {
            throw new RuntimeException('Chain33 CreateTransaction: unexpected response');
        }

        $signedTx = $rpc->send('Chain33.SignRawTx', [
            $unsignedTx,
            $privateKey,
            '',
        ]);

        if (!is_string($signedTx)) {
            throw new RuntimeException('Chain33 SignRawTx: unexpected response');
        }

        $sendResult = $rpc->send('Chain33.SendTransaction', [$signedTx]);

        if (is_string($sendResult)) {
            $txHash = $sendResult;
        } elseif (is_array($sendResult) && isset($sendResult['hash'])) {
            $txHash = $sendResult['hash'];
        } else {
            throw new RuntimeException('Chain33 SendTransaction: unexpected response');
        }

        $receipt = $this->pollReceipt($rpc, $txHash);
        $contractAddress = $this->extractContractAddress($receipt);

        return [
            'contract_address' => $contractAddress,
            'tx_hash' => $txHash,
        ];
    }

    /**
     * 压缩公钥
     *
     * @param  string  $uncompressedKey  未压缩公钥
     *
     * @return string 压缩后的公钥
     */
    private static function compressPublicKey(string $uncompressedKey): string
    {
        $x = substr($uncompressedKey, 2, 64);
        $y = substr($uncompressedKey, 66, 64);

        $prefix = (hexdec(substr($y, -1)) % 2 === 0) ? '02' : '03';

        return $prefix.$x;
    }

    /**
     * Chain33 Base58 编码
     *
     * @param  string  $hex  十六进制字符串
     *
     * @return string Base58 编码结果
     */
    private static function chain33Base58Encode(string $hex): string
    {
        $charset = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $res = '';
        $value = gmp_init($hex, 16);

        while (gmp_cmp($value, 0) > 0) {
            $qr = gmp_div_qr($value, 58);
            $value = $qr[0];
            $res .= $charset[(int) gmp_strval($qr[1])];
        }

        $leading = '';
        $i = 0;
        while ($hex[$i] === '0') {
            if ($i !== 0 && $i % 2 === 1) {
                $leading .= '1';
            }
            $i++;
        }

        return $leading.strrev($res);
    }

    /**
     * 从私钥获取压缩公钥
     *
     * @param  string  $privateKey  私钥
     *
     * @return string 压缩公钥
     */
    private function getCompressedPublicKeyFromPrivateKey(string $privateKey): string
    {
        return new EC('secp256k1')->keyFromPrivate($privateKey)->getPublic(true, 'hex');
    }

    /**
     * 移除 0x 前缀
     *
     * @param  string  $hex  十六进制字符串
     *
     * @return string 移除前缀后的字符串
     */
    private static function strip0x(string $hex): string
    {
        return str_replace('0x', '', $hex);
    }

    /**
     * 轮询获取交易回执
     *
     * @param  RpcClient  $rpc  RPC 客户端
     * @param  string  $txHash  交易哈希
     *
     * @throws RuntimeException 轮询失败
     *
     * @return array<int|string, mixed> 交易回执
     */
    private function pollReceipt(RpcClient $rpc, string $txHash): array
    {
        for ($i = 0; $i < 30; $i++) {
            try {
                $receipt = $rpc->send('Chain33.QueryTransaction', [$txHash]);

                if (is_array($receipt) && !empty($receipt)) {
                    return $receipt;
                }
            } catch (Exception) {
                // Transaction is not indexed yet.
            }

            usleep(2000 * 1000);
        }

        throw new RuntimeException("Chain33 receipt not found after 30 attempts: $txHash");
    }

    /**
     * 从回执中提取合约地址
     *
     * @param  array  $receipt  交易回执
     *
     * @return string 合约地址
     */
    private function extractContractAddress(array $receipt): string
    {
        return $receipt['contractAddr']
            ?? $receipt['contract_address']
            ?? $receipt['contractAddress']
            ?? '';
    }
}
