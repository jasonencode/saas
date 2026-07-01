<?php

namespace App\Extensions\BlockChain\Adapters;

use App\Contracts\NetworkAdapterInterface;
use App\Extensions\BlockChain\Abi\AbiEncoder;
use App\Extensions\BlockChain\Adapters\Traits\Secp256k1KeyOps;
use App\Extensions\BlockChain\Rpc\RpcClient;
use Elliptic\EC;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use JsonException;
use RuntimeException;

class Chain33Adapter implements NetworkAdapterInterface
{
    use Secp256k1KeyOps;

    /**
     * @throws RuntimeException|ConnectionException
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
     * @throws RuntimeException|ConnectionException
     *
     * @return array<int|string, mixed>
     */
    public function getPeers(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array
    {
        $rpc = new RpcClient($rpcUrl, 30);

        return $rpc->send('Chain33.GetPeerInfo')['peers'] ?? [];
    }

    /**
     * @throws RuntimeException|ConnectionException
     *
     * @return array{isSync: bool, netInfo: array<int|string, mixed>}
     */
    public function getSyncStatus(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): array
    {
        $rpc = new RpcClient($rpcUrl, 30);

        return [
            'isSync' => (bool) $rpc->send('Chain33.IsSync'),
            'netInfo' => $rpc->send('Chain33.GetNetInfo') ?? [],
        ];
    }

    public function getAddressFromPrivateKey(string $privateKey): string
    {
        return $this->getAddressFromPublicKey($this->getCompressedPublicKeyFromPrivateKey($privateKey));
    }

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
     * @param  array<int, mixed>  $constructorArgs
     *
     * @throws RuntimeException|JsonException|ConnectionException
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

    private static function compressPublicKey(string $uncompressedKey): string
    {
        $x = substr($uncompressedKey, 2, 64);
        $y = substr($uncompressedKey, 66, 64);

        $prefix = (hexdec(substr($y, -1)) % 2 === 0) ? '02' : '03';

        return $prefix.$x;
    }

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

    private function getCompressedPublicKeyFromPrivateKey(string $privateKey): string
    {
        return new EC('secp256k1')->keyFromPrivate($privateKey)->getPublic(true, 'hex');
    }

    private static function strip0x(string $hex): string
    {
        return str_replace('0x', '', $hex);
    }

    /**
     * @throws RuntimeException
     *
     * @return array<int|string, mixed>
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

    private function extractContractAddress(array $receipt): string
    {
        return $receipt['contractAddr']
            ?? $receipt['contract_address']
            ?? $receipt['contractAddress']
            ?? '';
    }
}
