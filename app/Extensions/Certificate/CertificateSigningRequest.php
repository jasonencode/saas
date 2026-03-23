<?php

namespace App\Extensions\Certificate;

use Illuminate\Support\Arr;
use OpenSSLAsymmetricKey;
use OpenSSLCertificate;
use Random\RandomException;
use RuntimeException;

/**
 * 生成CSR
 */
class CertificateSigningRequest
{
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
     * 自签名CA根证书
     *
     * @param  string  $csr
     * @param  OpenSSLAsymmetricKey  $privateKey
     * @param  int  $days
     * @param  array  $options
     * @return string
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
     * 通过上级证书，签发子证书
     *
     * @param  string  $csr
     * @param  OpenSSLAsymmetricKey  $caKey
     * @param  OpenSSLCertificate  $caCert
     * @param  int  $days
     * @param  array  $options
     * @return string
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

    private static function makeSerialNo(): int
    {
        try {
            $serial = random_bytes(6);

            return (int) abs(hexdec(bin2hex($serial)));
        } catch (RandomException) {
            throw new RuntimeException('Failed to generate serial number.');
        }
    }
}
