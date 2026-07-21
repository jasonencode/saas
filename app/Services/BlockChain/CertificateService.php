<?php

namespace App\Services\BlockChain;

use App\Contracts\ServiceInterface;
use App\Enums\BlockChain\CertificateType;
use App\Models\BlockChain\Certificate;
use App\Support\Certificate\CertificateSigningRequest;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class CertificateService implements ServiceInterface
{
    /**
     * 签发证书
     */
    public function signCertificate(Certificate $certificate, Certificate $intermediate, string $passphrase, int $days): Certificate
    {
        // 验证中间证书有效期
        if ($intermediate->updated_at->addDays($intermediate->days)->isBefore(now()->addDays($days))) {
            throw new InvalidArgumentException('中间证书有效期不能超过根证书有效期');
        }

        // 验证密码
        if (!Hash::check($passphrase, $intermediate->password)) {
            throw new InvalidArgumentException('中间证书密码错误');
        }

        // 生成私钥对
        $pk = $certificate->sign_type->getPrivateKey();
        $keyPair = $pk->export();

        // 创建 CSR
        $csr = CertificateSigningRequest::make(
            $certificate->dn,
            $keyPair->getPrivateKeyResource(),
            $pk->getOptions()
        );

        // 签名证书
        $cert = CertificateSigningRequest::sign(
            $csr,
            openssl_pkey_get_private($intermediate->private_key, $intermediate['password']),
            openssl_x509_read($intermediate->certificate),
            $days,
            $pk->getOptions()
        );

        // 更新证书信息
        $certificate->parent_id = $intermediate->id;
        $certificate->csr = $csr;
        $certificate->private_key = $keyPair->getPrivateKey();
        $certificate->certificate = $cert;
        $certificate->days = $days;
        $certificate->status = true;
        $certificate->save();

        return $certificate;
    }

    /**
     * 自签CA证书
     */
    public function selfSignCaCert(Certificate $certificate): Certificate
    {
        $pk = $certificate->sign_type->getPrivateKey();
        $keyPair = $pk->password($certificate->password)->export();

        $csr = CertificateSigningRequest::make(
            $certificate->dn,
            $keyPair->getPrivateKeyResource($certificate->password),
            $pk->getOptions()
        );

        $cert = CertificateSigningRequest::selfSignCaCert(
            $csr,
            $keyPair->getPrivateKeyResource($certificate->password),
            $certificate->days,
            $pk->getOptions()
        );

        $certificate->csr = $csr;
        $certificate->certificate = $cert;
        $certificate->private_key = $keyPair->getPrivateKey();
        $certificate->status = true;
        $certificate->save();

        return $certificate;
    }

    /**
     * 签发中间证书
     */
    public function signIntermediate(Certificate $certificate, Certificate $ca, string $passphrase, int $days): Certificate
    {
        // 验证CA证书类型
        if ($ca->type !== CertificateType::CA) {
            throw new InvalidArgumentException('只能使用CA证书签发中间证书');
        }

        // 验证CA证书有效期
        if ($ca->updated_at->addDays($ca->days)->isBefore(now()->addDays($days))) {
            throw new InvalidArgumentException('CA证书有效期不足');
        }

        // 验证密码
        if (!Hash::check($passphrase, $ca->password)) {
            throw new InvalidArgumentException('CA证书密码错误');
        }

        $pk = $certificate->sign_type->getPrivateKey();
        $keyPair = $pk->password($certificate->password)->export();

        $csr = CertificateSigningRequest::make(
            $certificate->dn,
            $keyPair->getPrivateKeyResource($certificate->password),
            $pk->getOptions()
        );

        $cert = CertificateSigningRequest::sign(
            $csr,
            openssl_pkey_get_private($ca->private_key, $ca->password),
            openssl_x509_read($ca->certificate),
            $days,
            $pk->getOptions()
        );

        $certificate->parent_id = $ca->id;
        $certificate->csr = $csr;
        $certificate->certificate = $cert;
        $certificate->private_key = $keyPair->getPrivateKey();
        $certificate->days = $days;
        $certificate->status = true;
        $certificate->save();

        return $certificate;
    }

    /**
     * 下载证书内容
     */
    public function download(Certificate $certificate): string
    {
        $content = "-----BEGIN CERTIFICATE-----\n";
        $content .= chunk_split(base64_encode($certificate->certificate), 64);
        $content .= "-----END CERTIFICATE-----\n";

        return $content;
    }
}
