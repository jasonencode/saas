<?php

namespace App\Support\BlockChain\Adapters\Traits;

use Elliptic\EC;
use kornrunner\Keccak;
use Random\Randomizer;
use Tuupola\Base58;

trait Secp256k1KeyOps
{
    private static ?EC $secp256k1 = null;

    private static function ec(): EC
    {
        return self::$secp256k1 ??= new EC('secp256k1');
    }

    public function generatePrivateKey(): string
    {
        do {
            $entropy = new Randomizer()->getBytes(32);
            $privateKey = bin2hex($entropy);
        } while (!$this->validatePrivateKey($privateKey));

        return $privateKey;
    }

    public function validatePrivateKey(string $privateKey): bool
    {
        if (str_starts_with($privateKey, '0x')) {
            $privateKey = substr($privateKey, 2);
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
        return self::ec()->keyFromPrivate($privateKey)->getPublic(false, 'hex');
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

    public function getCompressedPublicKeyFromPrivateKey(string $privateKey): string
    {
        return self::ec()->keyFromPrivate($privateKey)->getPublic(true, 'hex');
    }

    protected function fiscoAddressFromPublicKey(string $publicKey): string
    {
        $publicKey = $this->normalizePublicKey($publicKey);
        $hash = hash('sha3-256', hex2bin($publicKey));

        return '0x'.substr($hash, -40);
    }

    protected function btcAddressFromPublicKey(string $publicKey, string $versionByte = '00'): string
    {
        if (str_starts_with($publicKey, '0x')) {
            $publicKey = substr($publicKey, 2);
        }

        // BTC 风格地址必须使用压缩公钥来生成 hash160
        if (str_starts_with($publicKey, '04')) {
            $publicKey = $this->compressPublicKey($publicKey);
        }

        $sha256 = hash('sha256', hex2bin($publicKey), true);
        $ripemd160 = hash('ripemd160', $sha256, true);

        $networkBytes = hex2bin($versionByte).$ripemd160;

        $hash = hash('sha256', hash('sha256', $networkBytes, true), true);
        $checksum = substr($hash, 0, 4);

        return new Base58(['characters' => Base58::BITCOIN])->encode($networkBytes.$checksum);
    }

    protected function compressPublicKey(string $uncompressedKey): string
    {
        // 去掉 04 前缀，取 x 坐标（前64 hex）
        $x = substr($uncompressedKey, 2, 64);
        $y = substr($uncompressedKey, 66, 64);

        // y 坐标最后一位为偶数时前缀 02，奇数时前缀 03
        $prefix = (hexdec(substr($y, -1)) % 2 === 0) ? '02' : '03';

        return $prefix.$x;
    }
}
