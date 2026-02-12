<?php

namespace App\Extensions\BlockChain\Adapters;

use App\Contracts\NetworkAdapter;
use App\Extensions\BlockChain\Adapters\Traits\Secp256k1KeyOps;

class FiscoAdapter implements NetworkAdapter
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
}
