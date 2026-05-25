<?php

namespace App\Extensions\BlockChain\Adapters;

use App\Contracts\NetworkAdapterInterface;
use App\Extensions\BlockChain\Adapters\Traits\Secp256k1KeyOps;
use RuntimeException;

abstract class AbstractCompressedKeyAdapter implements NetworkAdapterInterface
{
    use Secp256k1KeyOps;

    public function getAddressFromPrivateKey(string $privateKey): string
    {
        return $this->getAddressFromPublicKey($this->getCompressedPublicKeyFromPrivateKey($privateKey));
    }

    public function getAddressFromPublicKey(string $publicKey): string
    {
        return $this->btcAddressFromPublicKey($publicKey);
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
