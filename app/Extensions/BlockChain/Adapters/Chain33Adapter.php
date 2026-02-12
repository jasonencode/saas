<?php

namespace App\Extensions\BlockChain\Adapters;

use App\Contracts\NetworkAdapter;
use App\Extensions\BlockChain\Adapters\Traits\Secp256k1KeyOps;

class Chain33Adapter implements NetworkAdapter
{
    use Secp256k1KeyOps;

    public function getAddressFromPublicKey(string $publicKey): string
    {
        return '';
    }

    public function getAddressFromPrivateKey(string $privateKey): string
    {
        return '';
    }
}