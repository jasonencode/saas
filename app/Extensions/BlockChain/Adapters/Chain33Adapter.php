<?php

namespace App\Extensions\BlockChain\Adapters;

use App\Extensions\BlockChain\Abi\AbiEncoder;
use App\Extensions\BlockChain\Rpc\RpcClient;
use Exception;
use RuntimeException;

class Chain33Adapter extends AbstractCompressedKeyAdapter
{
    public function getBlockNumber(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): int
    {
        $rpc = new RpcClient($rpcUrl, 30);

        $result = $rpc->send('Chain33.GetAccount', ['' /* 任意地址 */, 'DEV' /* 或实际 execer */]);

        // Chain33 返回块高通常在 result.blocknum 或 result
        if (is_array($result) && isset($result['blocknum'])) {
            return (int) $result['blocknum'];
        }

        // fallback: 直接返回数值
        return (int) $result;
    }

    /**
     * Chain33 EVM 合约部署流程：
     * 1. chain33.CreateTransaction – 创建未签名交易（execer="evm", actionName="deploy"）
     * 2. chain33.SignRawTx – 使用私钥签名
     * 3. chain33.SendTransaction – 广播签名交易
     * 4. chain33.QueryTransaction – 轮询获取回执
     */
    public function deployContract(
        string $privateKey,
        string $bytecode,
        ?string $abi = null,
        array $constructorArgs = [],
        string $rpcUrl = '',
    ): array {
        $rpc = new RpcClient($rpcUrl, 60);

        // 1. ABI 编码构造参数
        $encodedArgs = AbiEncoder::encodeConstructor($abi ?? '', $constructorArgs);
        $deployData = self::strip0x($bytecode).$encodedArgs;

        // 2. 构建部署负载
        $payload = [
            'code' => '0x'.$deployData,
            'abi' => $abi ?? '',
            'parameter' => $encodedArgs ? '0x'.$encodedArgs : '',
        ];

        // 3. 创建未签名交易
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

        // 4. 签名原始交易
        $signedTx = $rpc->send('Chain33.SignRawTx', [
            $unsignedTx,
            $privateKey,
            '', // 密码（私钥签名时为空）
        ]);

        if (!is_string($signedTx)) {
            throw new RuntimeException('Chain33 SignRawTx: unexpected response');
        }

        // 5. 发送签名交易
        $sendResult = $rpc->send('Chain33.SendTransaction', [$signedTx]);

        if (is_string($sendResult)) {
            $txHash = $sendResult;
        } elseif (is_array($sendResult) && isset($sendResult['hash'])) {
            $txHash = $sendResult['hash'];
        } else {
            throw new RuntimeException('Chain33 SendTransaction: unexpected response');
        }

        // 6. 轮询获取回执
        $receipt = $this->pollChain33Receipt($rpc, $txHash);

        // 7. 从回执日志中提取合约地址
        $contractAddress = $this->extractContractAddress($receipt);

        return [
            'contract_address' => $contractAddress,
            'tx_hash' => $txHash,
        ];
    }

    /**
     * 轮询 Chain33.QueryTransaction 直到确认或超时
     *
     * @throws RuntimeException
     */
    private function pollChain33Receipt(RpcClient $rpc, string $txHash): array
    {
        for ($i = 0; $i < 30; $i++) {
            try {
                $receipt = $rpc->send('Chain33.QueryTransaction', [$txHash]);

                if (is_array($receipt) && !empty($receipt)) {
                    return $receipt;
                }
            } catch (Exception) {
                // 尚未找到，重试
            }

            usleep(2000 * 1000);
        }

        throw new RuntimeException("Chain33 receipt not found after 30 attempts: $txHash");
    }

    /**
     * 从 Chain33 交易回执中提取合约地址
     */
    private function extractContractAddress(array $receipt): string
    {
        // Chain33 在回执中返回合约地址
        // 格式可能为 contractAddr、contract_address 或在 receipt.logs 中
        return $receipt['contractAddr']
            ?? $receipt['contract_address']
            ?? $receipt['contractAddress']
            ?? '';
    }

    private static function strip0x(string $hex): string
    {
        return str_replace('0x', '', $hex);
    }
}
