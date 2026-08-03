<?php

namespace App\Support\BlockChain\Adapters\Traits;

use Elliptic\EC;
use kornrunner\Keccak;
use Random\Randomizer;
use Tuupola\Base58;

/**
 * SECP256K1 密钥操作 trait
 *
 * 提供私钥生成、公钥派生、地址生成（EVM/FISCO/BTC）等能力。
 */
trait Secp256k1KeyOps
{
    private static ?EC $secp256k1 = null;

    /**
     * 获取 EC 实例
     *
     * @return EC EC 实例
     */
    private static function ec(): EC
    {
        return self::$secp256k1 ??= new EC('secp256k1');
    }

    /**
     * 生成私钥
     *
     * @return string 私钥（十六进制）
     */
    public function generatePrivateKey(): string
    {
        do {
            $entropy = new Randomizer()->getBytes(32);
            $privateKey = bin2hex($entropy);
        } while (!$this->validatePrivateKey($privateKey));

        return $privateKey;
    }

    /**
     * 验证私钥是否有效
     *
     * @param  string  $privateKey  私钥
     *
     * @return bool 是否有效
     */
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

    /**
     * 从私钥获取公钥
     *
     * @param  string  $privateKey  私钥
     *
     * @return string 公钥（未压缩）
     */
    public function getPublicKeyFromPrivateKey(string $privateKey): string
    {
        return self::ec()->keyFromPrivate($privateKey)->getPublic(false, 'hex');
    }

    /**
     * 从公钥获取 EVM 地址
     *
     * @param  string  $publicKey  公钥
     *
     * @return string EVM 地址
     */
    protected function evmAddressFromPublicKey(string $publicKey): string
    {
        $publicKey = $this->normalizePublicKey($publicKey);
        $hash = Keccak::hash(hex2bin($publicKey), 256);
        $address = '0x'.substr($hash, -40);

        return $this->toChecksumAddress($address);
    }

    /**
     * 标准化公钥（移除前缀）
     *
     * @param  string  $publicKey  公钥
     *
     * @return string 标准化后的公钥
     */
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

    /**
     * 转换为校验和地址
     *
     * @param  string  $address  地址
     *
     * @return string 校验和地址
     */
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

    /**
     * 从私钥获取压缩公钥
     *
     * @param  string  $privateKey  私钥
     *
     * @return string 压缩公钥
     */
    public function getCompressedPublicKeyFromPrivateKey(string $privateKey): string
    {
        return self::ec()->keyFromPrivate($privateKey)->getPublic(true, 'hex');
    }

    /**
     * 从公钥获取 FISCO 地址
     *
     * @param  string  $publicKey  公钥
     *
     * @return string FISCO 地址
     */
    protected function fiscoAddressFromPublicKey(string $publicKey): string
    {
        $publicKey = $this->normalizePublicKey($publicKey);
        $hash = hash('sha3-256', hex2bin($publicKey));

        return '0x'.substr($hash, -40);
    }

    /**
     * 从公钥获取 BTC 地址
     *
     * @param  string  $publicKey  公钥
     * @param  string  $versionByte  版本字节
     *
     * @return string BTC 地址
     */
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

    /**
     * 压缩公钥
     *
     * @param  string  $uncompressedKey  未压缩公钥
     *
     * @return string 压缩后的公钥
     */
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
