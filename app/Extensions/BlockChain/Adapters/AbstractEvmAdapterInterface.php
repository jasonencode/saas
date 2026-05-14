<?php

namespace App\Extensions\BlockChain\Adapters;

use App\Contracts\NetworkAdapterInterface;
use App\Extensions\BlockChain\Abi\AbiEncoder;
use App\Extensions\BlockChain\Adapters\Traits\Secp256k1KeyOps;
use App\Extensions\BlockChain\Rlp\RlpEncoder;
use App\Extensions\BlockChain\Rpc\RpcClient;
use Elliptic\EC;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use kornrunner\Keccak;
use RuntimeException;

abstract class AbstractEvmAdapterInterface implements NetworkAdapterInterface
{
    use Secp256k1KeyOps;

    public function getAddressFromPrivateKey(string $privateKey): string
    {
        return $this->getAddressFromPublicKey($this->getPublicKeyFromPrivateKey($privateKey));
    }

    public function getAddressFromPublicKey(string $publicKey): string
    {
        return $this->evmAddressFromPublicKey($publicKey);
    }

    private const int MAX_POLL_ATTEMPTS = 30;

    private const int POLL_INTERVAL_MS = 2000;

    private const int GAS_LIMIT_FALLBACK = 5_000_000;

    public function deployContract(
        string $privateKey,
        string $bytecode,
        ?string $abi = null,
        array $constructorArgs = [],
        string $rpcUrl = '',
    ): array
    {
        $rpc = new RpcClient($rpcUrl, 60);

        // 1. 派生部署者地址
        $fromAddress = $this->getAddressFromPrivateKey($privateKey);
        if (!str_starts_with($fromAddress, '0x')) {
            $fromAddress = '0x'.$fromAddress;
        }

        // 2. 获取 nonce（pending 以包含未确认交易）
        $nonce = $rpc->send('eth_getTransactionCount', [$fromAddress, 'pending']);

        // 3. 获取 gas 价格
        $gasPrice = $rpc->send('eth_gasPrice');

        // 4. ABI 编码构造参数并构建部署数据
        $encodedArgs = AbiEncoder::encodeConstructor($abi ?? '', $constructorArgs);
        $data = self::normalizeHex($bytecode).$encodedArgs;

        // 5. 估算 gas（失败时使用默认值）
        try {
            $gasLimit = $rpc->send('eth_estimateGas', [[
                'from' => $fromAddress,
                'data' => '0x'.$data,
            ]]);
            $gasLimitInt = self::hexToInt($gasLimit);
            if ($gasLimitInt < 21000) {
                $gasLimitInt = self::GAS_LIMIT_FALLBACK;
            }
        } catch (Exception) {
            $gasLimitInt = self::GAS_LIMIT_FALLBACK;
        }

        // 6. 获取链 ID（EIP-155）
        $chainId = self::hexToInt($rpc->send('eth_chainId'));

        // 7. 构建用于签名的原始交易 RLP
        $nonceBin = self::bigIntToBin($nonce);
        $gasPriceBin = self::bigIntToBin($gasPrice);
        $gasLimitBin = self::bigIntToBin('0x'.dechex($gasLimitInt));
        $toBin = ''; // 合约创建时为空
        $valueBin = ''; // 零值
        $dataBin = hex2bin($data);

        $txForSigning = RlpEncoder::encode([
            $nonceBin, $gasPriceBin, $gasLimitBin,
            $toBin, $valueBin, $dataBin,
            $chainId, 0, 0, // EIP-155: chainId, v(r), v(s) 占位符
        ]);

        // 8. 未签名交易的 Keccak-256 哈希
        $hashBin = Keccak::hash($txForSigning, 256);

        // 9. 使用 secp256k1 ECDSA 签名
        $ec = new EC('secp256k1');
        $key = $ec->keyFromPrivate($privateKey);
        $signature = $key->sign($hashBin, ['canonical' => true]);

        // 10. 提取 r, s, v
        $rHex = str_pad($signature->r->toString(16), 64, '0', STR_PAD_LEFT);
        $sHex = str_pad($signature->s->toString(16), 64, '0', STR_PAD_LEFT);
        $rBin = hex2bin($rHex);
        $sBin = hex2bin($sHex);
        $v = $chainId * 2 + 35 + ($signature->recoveryParam ?? 0);

        // 11. 构建最终签名交易 RLP
        $signedTxBin = RlpEncoder::encode([
            $nonceBin, $gasPriceBin, $gasLimitBin,
            $toBin, $valueBin, $dataBin,
            $v, $rBin, $sBin,
        ]);

        $signedTxHex = '0x'.bin2hex($signedTxBin);

        // 12. 广播
        $txHash = $rpc->send('eth_sendRawTransaction', [$signedTxHex]);

        // 13. 轮询获取回执
        $receipt = $this->pollReceipt($rpc, $txHash);

        return [
            'contract_address' => $receipt['contractAddress'] ?? '',
            'tx_hash' => $txHash,
        ];
    }

    /**
     * 轮询 eth_getTransactionReceipt 直到确认或超时
     *
     * @throws RuntimeException|ConnectionException
     */
    private function pollReceipt(RpcClient $rpc, string $txHash): array
    {
        for ($i = 0; $i < self::MAX_POLL_ATTEMPTS; $i++) {
            $receipt = $rpc->send('eth_getTransactionReceipt', [$txHash]);

            if ($receipt !== null) {
                return $receipt;
            }

            usleep(self::POLL_INTERVAL_MS * 1000);
        }

        throw new RuntimeException(sprintf(
            'Transaction receipt not found after %d attempts: %s',
            self::MAX_POLL_ATTEMPTS,
            $txHash
        ));
    }

    /**
     * 去除 0x 前缀并确保十六进制字符串长度为偶数
     */
    private static function normalizeHex(string $hex): string
    {
        $hex = str_replace('0x', '', $hex);

        if (strlen($hex) % 2 !== 0) {
            $hex = '0'.$hex;
        }

        return $hex;
    }

    /**
     * 将十六进制字符串（带或不带 0x 前缀）转换为整数
     */
    private static function hexToInt(string $hex): int
    {
        return (int) hexdec(str_replace('0x', '', $hex));
    }

    /**
     * 将十六进制字符串转换为最小大端序二进制表示
     * （去除前导零，零值返回空字符串）
     */
    private static function bigIntToBin(string $hex): string
    {
        $hex = ltrim(str_replace('0x', '', $hex), '0');

        if ($hex === '') {
            return '';
        }

        if (strlen($hex) % 2 !== 0) {
            $hex = '0'.$hex;
        }

        return hex2bin($hex);
    }
}
