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
use JsonException;
use kornrunner\Keccak;
use RuntimeException;

class FiscoAdapter implements NetworkAdapterInterface
{
    use Secp256k1KeyOps;

    private const int MAX_POLL_ATTEMPTS = 30;

    private const int POLL_INTERVAL_MS = 2000;

    private const int GAS_LIMIT_FALLBACK = 5_000_000;

    /**
     * @throws ConnectionException
     */
    public function getBlockNumber(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): int
    {
        $groupId ??= 1;

        $rpc = new RpcClient($rpcUrl, 30, $sslOptions);

        return (int) hexdec($rpc->send('getBlockNumber', [$groupId]));
    }

    /**
     * @throws ConnectionException
     *
     * @return array<int|string, mixed>
     */
    public function getPeers(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array
    {
        $groupId ??= 1;

        $rpc = new RpcClient($rpcUrl, 30, $sslOptions);

        return $rpc->send('getPeers', [$groupId]) ?? [];
    }

    /**
     * @throws ConnectionException|JsonException
     *
     * @return array<int|string, mixed>
     */
    public function getSyncStatus(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array
    {
        $rpc = new RpcClient($rpcUrl, 30, $sslOptions);

        return json_decode($rpc->send('getSyncStatus', [$groupId]), true, 512, JSON_THROW_ON_ERROR) ?? [];
    }

    /**
     * @param  array<int, mixed>  $constructorArgs
     *
     * @throws RuntimeException|ConnectionException|JsonException
     * @throws Exception
     *
     * @return array{contract_address: string, tx_hash: string}
     */
    public function deployContract(
        string $privateKey,
        string $bytecode,
        ?string $abi = null,
        array $constructorArgs = [],
        string $rpcUrl = '',
        array $sslOptions = [],
    ): array {
        $rpc = new RpcClient($rpcUrl, 60, $sslOptions);

        $fromAddress = $this->getAddressFromPrivateKey($privateKey);
        if (!str_starts_with($fromAddress, '0x')) {
            $fromAddress = '0x'.$fromAddress;
        }

        $nonce = $rpc->send('eth_getTransactionCount', [$fromAddress, 'pending']);
        $gasPrice = $rpc->send('eth_gasPrice');

        $encodedArgs = AbiEncoder::encodeConstructor($abi ?? '', $constructorArgs);
        $data = self::normalizeHex($bytecode).$encodedArgs;

        try {
            $gasLimit = $rpc->send('eth_estimateGas', [
                [
                    'from' => $fromAddress,
                    'data' => '0x'.$data,
                ],
            ]);
            $gasLimitInt = self::hexToInt($gasLimit);
            if ($gasLimitInt < 21000) {
                $gasLimitInt = self::GAS_LIMIT_FALLBACK;
            }
        } catch (Exception) {
            $gasLimitInt = self::GAS_LIMIT_FALLBACK;
        }

        $chainId = self::hexToInt($rpc->send('eth_chainId'));

        $nonceBin = self::bigIntToBin($nonce);
        $gasPriceBin = self::bigIntToBin($gasPrice);
        $gasLimitBin = self::bigIntToBin('0x'.dechex($gasLimitInt));
        $toBin = '';
        $valueBin = '';
        $dataBin = hex2bin($data);

        $txForSigning = RlpEncoder::encode([
            $nonceBin, $gasPriceBin, $gasLimitBin,
            $toBin, $valueBin, $dataBin,
            $chainId, 0, 0,
        ]);

        $hashBin = Keccak::hash($txForSigning, 256);

        $ec = new EC('secp256k1');
        $key = $ec->keyFromPrivate($privateKey);
        $signature = $key->sign($hashBin, ['canonical' => true]);

        $rHex = str_pad($signature->r->toString(16), 64, '0', STR_PAD_LEFT);
        $sHex = str_pad($signature->s->toString(16), 64, '0', STR_PAD_LEFT);
        $rBin = hex2bin($rHex);
        $sBin = hex2bin($sHex);
        $v = $chainId * 2 + 35 + ($signature->recoveryParam ?? 0);

        $signedTxBin = RlpEncoder::encode([
            $nonceBin, $gasPriceBin, $gasLimitBin,
            $toBin, $valueBin, $dataBin,
            $v, $rBin, $sBin,
        ]);

        $signedTxHex = '0x'.bin2hex($signedTxBin);
        $txHash = $rpc->send('eth_sendRawTransaction', [$signedTxHex]);
        $receipt = $this->pollReceipt($rpc, $txHash);

        return [
            'contract_address' => $receipt['contractAddress'] ?? '',
            'tx_hash' => $txHash,
        ];
    }

    public function getAddressFromPrivateKey(string $privateKey): string
    {
        return $this->getAddressFromPublicKey($this->getPublicKeyFromPrivateKey($privateKey));
    }

    public function getAddressFromPublicKey(string $publicKey): string
    {
        if (str_starts_with($publicKey, '0x')) {
            $publicKey = substr($publicKey, 2);
        }

        if (str_starts_with($publicKey, '04')) {
            $publicKey = substr($publicKey, 2);
        }

        $hash = hash('sha3-256', hex2bin($publicKey));

        return '0x'.substr($hash, -40);
    }

    private static function normalizeHex(string $hex): string
    {
        $hex = str_replace('0x', '', $hex);

        if (strlen($hex) % 2 !== 0) {
            $hex = '0'.$hex;
        }

        return $hex;
    }

    private static function hexToInt(string $hex): int
    {
        return (int) hexdec(str_replace('0x', '', $hex));
    }

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

    /**
     * @throws RuntimeException|ConnectionException
     *
     * @return array<int|string, mixed>
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
}
