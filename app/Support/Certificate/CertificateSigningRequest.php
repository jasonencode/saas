<?php

namespace App\Support\Certificate;

use Illuminate\Support\Arr;
use OpenSSLAsymmetricKey;
use OpenSSLCertificate;
use Random\RandomException;
use RuntimeException;

/**
 * 证书签名请求
 */
class CertificateSigningRequest
{
    /**
     * 创建证书签名请求
     *
     * @param  array  $distinguishedNames  可分辨名称
     * @param  OpenSSLAsymmetricKey  $privateKey  私钥
     * @param  array  $options  配置选项
     *
     * @return string CSR 内容
     */
    public static function make(
        array $distinguishedNames,
        OpenSSLAsymmetricKey $privateKey,
        array $options = []
    ): string {
        $dn = [
            'countryName' => 'CN',
        ];
        $dn = array_merge($dn, $distinguishedNames);
        $dn = Arr::only($dn, [
            'countryName',
            'stateOrProvinceName',
            'localityName',
            'organizationName',
            'organizationalUnitName',
            'commonName',
        ]);

        $csr = openssl_csr_new(array_filter($dn), $privateKey, $options);
        openssl_csr_export($csr, $csrOut);

        return $csrOut;
    }

    /**
     * 自签名 CA 根证书
     *
     * @param  string  $csr  证书签名请求
     * @param  OpenSSLAsymmetricKey  $privateKey  私钥
     * @param  int  $days  有效天数
     * @param  array  $options  配置选项
     *
     * @return string PEM 格式的证书
     */
    public static function selfSignCaCert(
        string $csr,
        OpenSSLAsymmetricKey $privateKey,
        int $days = 3650,
        array $options = []
    ): string {
        $cert = openssl_csr_sign($csr, null, $privateKey, $days, $options, self::makeSerialNo());
        openssl_x509_export($cert, $certPem);

        return $certPem;
    }

    /**
     * 生成序列号
     *
     *
     * @throws RuntimeException 生成序列号失败
     *
     * @return int 序列号
     */
    private static function makeSerialNo(): int
    {
        try {
            $serial = random_bytes(6);

            return (int) abs(hexdec(bin2hex($serial)));
        } catch (RandomException) {
            throw new RuntimeException('Failed to generate serial number.');
        }
    }

    /**
     * 通过上级证书签发子证书
     *
     * @param  string  $csr  证书签名请求
     * @param  OpenSSLAsymmetricKey  $caKey  CA 私钥
     * @param  OpenSSLCertificate  $caCert  CA 证书
     * @param  int  $days  有效天数
     * @param  array  $options  配置选项
     *
     * @return string PEM 格式的证书
     */
    public static function sign(
        string $csr,
        OpenSSLAsymmetricKey $caKey,
        OpenSSLCertificate $caCert,
        int $days = 365,
        array $options = []
    ): string {
        $cert = openssl_csr_sign($csr, $caCert, $caKey, $days, $options, self::makeSerialNo());
        openssl_x509_export($cert, $certPem);

        return $certPem;
    }
}
