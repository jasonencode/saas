<?php

namespace App\Extensions\BlockChain\Adapters\Traits;

use Elliptic\EC;
use kornrunner\Keccak;
use Random\Randomizer;

trait Secp256k1KeyOps
{
    public function generatePrivateKey(): string
    {
        $entropy = new Randomizer()->getBytes(32);
        $privateKey = bin2hex($entropy);

        while (!$this->validatePrivateKey($privateKey)) {
            $entropy = new Randomizer()->getBytes(32);
            $privateKey = bin2hex($entropy);
        }

        return $privateKey;
    }

    public function validatePrivateKey(string $privateKey): bool
    {
        if (str_starts_with($privateKey, '0x')) {
            $privateKey = ltrim($privateKey, '0x');
        }

        if (strlen($privateKey) !== 64) {
            return false;
        }

        if (!ctype_xdigit($privateKey)) {
            return false;
        }

        $maxKey = 'fffffffffffffffffffffffffffffffebaaedce6af48a03bbfd25e8cd0364141';

        return gmp_cmp('0x'.$privateKey, '0x'.$maxKey) < 0;
    }

    public function getPublicKeyFromPrivateKey(string $privateKey): string
    {
        $ec = new EC('secp256k1');

        return $ec->keyFromPrivate($privateKey)->getPublic(false, 'hex');
    }

    protected function evmAddressFromPublicKey(string $publicKey): string
    {
        $publicKey = $this->normalizePublicKey($publicKey);
        $hash = Keccak::hash(hex2bin($publicKey), 256);
        $address = '0x'.substr($hash, -40);

        return $this->toChecksumAddress($address);
    }

    protected function normalizePublicKey(string $publicKey): string
    {
        if (str_starts_with($publicKey, '0x')) {
            $publicKey = substr($publicKey, 2);
        }

        if (str_starts_with($publicKey, '04')) {
            $publicKey = substr($publicKey, 2);
        }

        return $publicKey;
    }

    protected function toChecksumAddress(string $address): string
    {
        $address = strtolower(str_replace('0x', '', $address));
        $hash = Keccak::hash(strtolower($address), 256);

        $ret = '0x';
        for ($i = 0; $i < 40; $i++) {
            $ret .= intval($hash[$i], 16) >= 8 ? strtoupper($address[$i]) : $address[$i];
        }

        return $ret;
    }
}
