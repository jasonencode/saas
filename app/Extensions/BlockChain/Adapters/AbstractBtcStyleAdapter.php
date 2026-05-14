<?php

namespace App\Extensions\BlockChain\Adapters;

use App\Contracts\NetworkAdapter;
use App\Extensions\BlockChain\Adapters\Traits\Secp256k1KeyOps;

abstract class AbstractBtcStyleAdapter implements NetworkAdapter
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
}
