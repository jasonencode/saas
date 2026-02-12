<?php

namespace App\Extensions\BlockChain\Adapters;

use App\Contracts\NetworkAdapter;
use App\Extensions\BlockChain\Adapters\Traits\Secp256k1KeyOps;
use kornrunner\Keccak;
use Tuupola\Base58;

class TronAdapter implements NetworkAdapter
{
    use Secp256k1KeyOps;

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
}
