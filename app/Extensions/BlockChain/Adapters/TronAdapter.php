<?php

namespace App\Extensions\BlockChain\Adapters;

use App\Contracts\NetworkAdapterInterface;
use App\Extensions\BlockChain\Abi\AbiEncoder;
use App\Extensions\BlockChain\Adapters\Traits\Secp256k1KeyOps;
use App\Extensions\BlockChain\Rpc\RpcClient;
use Elliptic\EC;
use Exception;
use kornrunner\Keccak;
use RuntimeException;
use Tuupola\Base58;

class TronAdapter implements NetworkAdapterInterface
{
    use Secp256k1KeyOps;

    public function getBlockNumber(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): int
    {
        $rpc = new RpcClient($rpcUrl, 30);

        $result = $rpc->postRaw('/wallet/getnowblock');

        return (int) ($result['blockHeader']['raw']['timestamp'] ?? 0);
    }

    public function getAddressFromPrivateKey(string $privateKey): string
    {
        $publicKey = $this->getPublicKeyFromPrivateKey($privateKey);

        return $this->getAddressFromPublicKey($publicKey);
    }

    public function getAddressFromPublicKey(string $publicKey): string
    {
        $publicKey = $this->normalizePublicKey($publicKey);

        $hash = Keccak::hash(hex2bin($publicKey), 256);
        $hash = substr($hash, -40);
        $addressHex = '41'.$hash;

        return $this->base58checkEncode($addressHex);
    }

    private function base58checkEncode(string $hex): string
    {
        $data = hex2bin($hex);
        $hash = hash('sha256', hash('sha256', $data, true), true);
        $checksum = substr($hash, 0, 4);

        return new Base58(['characters' => Base58::BITCOIN])->encode($data.$checksum);
    }

    /**
     * 将 TRON base58 地址转换为十六进制（带 0x41 前缀）
     */
    private function base58ToHex(string $base58): string
    {
        $decoded = new Base58(['characters' => Base58::BITCOIN])->decode($base58);
        $hex = bin2hex($decoded);

        // 去除校验和（最后 8 个十六进制字符 = 4 字节）
        return substr($hex, 0, 42); // 0x41 + 40 个十六进制字符 = 42 字符
    }

    /**
     * 从私钥获取 TRON 十六进制地址（带 41 前缀）
     */
    private function getAddressHexFromPrivateKey(string $privateKey): string
    {
        $publicKey = $this->getPublicKeyFromPrivateKey($privateKey);
        $publicKey = $this->normalizePublicKey($publicKey);

        $hash = Keccak::hash(hex2bin($publicKey), 256);

        // TRON 十六进制地址：0x41 + Keccak-256 哈希的后 20 字节
        return '41'.substr($hash, -40);
    }

    public function deployContract(
        string $privateKey,
        string $bytecode,
        ?string $abi = null,
        array $constructorArgs = [],
        string $rpcUrl = '',
    ): array {
        $rpc = new RpcClient($rpcUrl, 60);

        // 1. 获取所有者十六进制地址（TRON API 需要 0x41 前缀）
        $ownerAddressHex = $this->getAddressHexFromPrivateKey($privateKey);

        // 2. ABI 编码构造参数
        $encodedArgs = AbiEncoder::encodeConstructor($abi ?? '', $constructorArgs);
        $deployData = self::strip0x($bytecode).$encodedArgs;

        // 3. 创建未签名部署交易
        $unsignedTx = $rpc->postRaw('/wallet/deploycontract', [
            'owner_address' => $ownerAddressHex,
            'abi' => $abi ?? '',
            'bytecode' => '0x'.$deployData,
            'name' => '',
            'fee_limit' => 100_000_000, // 100 TRX
            'consume_user_resource_percent' => 30,
            'origin_energy_limit' => 10_000_000,
            'call_value' => 0,
        ]);

        $txId = $unsignedTx['txID']
            ?? throw new RuntimeException('TRON deploycontract: no txID in response');

        // 3.5 将合约名称转换为十六进制
        $unsignedTx['name'] = bin2hex($unsignedTx['name'] ?? '');

        // 4. 签名 txID（EIP-155 风格，但 v = 27 + recoveryParam）
        $signature = $this->tronSign($txId, $privateKey);

        // 5. 添加签名并广播
        $unsignedTx['signature'] = [$signature];

        $broadcastResult = $rpc->postRaw('/wallet/broadcasttransaction', $unsignedTx);

        $broadcastTxId = $broadcastResult['txid'] ?? $broadcastResult['result'] ?? null;
        if ($broadcastTxId === null) {
            $message = $broadcastResult['message'] ?? 'unknown TRON broadcast error';
            // 解码十六进制消息（如果存在）
            if (ctype_xdigit($message)) {
                $message = hex2bin($message);
            }
            throw new RuntimeException("TRON broadcast failed: $message");
        }

        // 6. 轮询获取交易信息（回执）
        $receipt = $this->pollTronReceipt($rpc, $txId);

        // 7. 提取合约地址
        // TRON 在回执中返回 contract_address，十六进制格式带 41 前缀
        $contractAddressHex = $receipt['contract_address']
            ?? $receipt['contractAddress']
            ?? '';

        $contractAddress = '';
        if ($contractAddressHex) {
            // 从十六进制（41...）转换为 base58 用户格式
            $contractAddress = $this->base58checkEncode($contractAddressHex);
        }

        return [
            'contract_address' => $contractAddress,
            'tx_hash' => $txId,
        ];
    }

    /**
     * 使用 ECDSA 签名 TRON 交易 ID
     *
     * TRON 签名使用 txID（raw_data 的 SHA-256），使用 secp256k1 签名。
     * 签名十六进制格式：r(32B) + s(32B) + v(1B)。
     * v 为 27 + recoveryParam（非 EIP-155 基于链 ID）
     */
    private function tronSign(string $txId, string $privateKey): string
    {
        $ec = new EC('secp256k1');
        $key = $ec->keyFromPrivate($privateKey);
        $signature = $key->sign(hex2bin($txId), ['canonical' => true]);

        $rHex = str_pad($signature->r->toString(16), 64, '0', STR_PAD_LEFT);
        $sHex = str_pad($signature->s->toString(16), 64, '0', STR_PAD_LEFT);
        $v = 27 + ($signature->recoveryParam ?? 0);

        return $rHex.$sHex.dechex($v);
    }

    /**
     * 轮询 TRON 交易回执直到确认或超时
     *
     * @throws RuntimeException
     */
    private function pollTronReceipt(RpcClient $rpc, string $txId, int $maxAttempts = 30): array
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            try {
                $info = $rpc->postRaw('/wallet/gettransactioninfo', [
                    'value' => $txId,
                ]);

                if (!empty($info) && !empty($info['id'])) {
                    return $info;
                }
            } catch (Exception) {
                // 尚未找到，重试
            }

            usleep(2000 * 1000); // 2 秒
        }

        throw new RuntimeException("TRON transaction info not found after $maxAttempts attempts: $txId");
    }

    /**
     * 去除 0x 前缀
     */
    private static function strip0x(string $hex): string
    {
        return str_replace('0x', '', $hex);
    }

    public function getPeers(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array
    {
        throw new RuntimeException(static::class.' does not support getPeers');
    }

    public function getSyncStatus(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array
    {
        throw new RuntimeException(static::class.' does not support getSyncStatus');
    }

}
