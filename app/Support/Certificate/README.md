# Certificate

X.509 证书管理，提供 RSA/EC 私钥生成、密钥对导出、CSR 创建和证书签发，用于构建区块链证书体系的 PKI 层级。

## 架构

```
PrivateKey          # 私钥生成（RSA / EC）
    ↓
KeyPair             # 密钥对 DTO
    ↓
CertificateSigningRequest  # CSR 创建 + 证书签发
```

## 目录结构

```
Certificate/
├── PrivateKey.php               # 私钥生成（RSA 1024/2048/4096，EC 256/384/512）
├── KeyPair.php                  # 密钥对 DTO（公钥 + 私钥 PEM）
└── CertificateSigningRequest.php # CSR 操作 + 自签名 CA + 子证书签发
```

## 核心类

| 类 | 职责 |
|----|------|
| `PrivateKey` | 通过 OpenSSL 生成 RSA/EC 私钥，单例模式 |
| `KeyPair` | 持有 PEM 格式的公私钥，提供 `getPrivateKeyResource()` |
| `CertificateSigningRequest` | 静态工具：`make()` 创建 CSR，`selfSignCaCert()` 自签名 CA，`sign()` 签发子证书 |

## 使用方式

```php
use App\Support\Certificate\PrivateKey;
use App\Support\Certificate\CertificateSigningRequest;

// 生成 CA 密钥对
$caKey = PrivateKey::makeEcKey('prime256v1');
$caCert = CertificateSigningRequest::selfSignCaCert(
    distinguishedName: ['CN' => 'My CA'],
    privateKey: $caKey,
    validityDays: 3650,
);

// 签发叶子证书
$leafKey = PrivateKey::makeEcKey('prime256v1');
$leafCert = CertificateSigningRequest::sign(
    caCert: $caCert,
    caPrivateKey: $caKey,
    csr: CertificateSigningRequest::make(
        distinguishedName: ['CN' => 'node1.example.com'],
        privateKey: $leafKey,
    ),
    validityDays: 365,
);
```

## 证书层级

```
CA（根证书，自签名）
├── Intermediate（中间证书，CA 签发）
│   ├── Leaf（叶子证书，Intermediate 签发）
│   └── Leaf
└── Intermediate
    └── Leaf
```

## 依赖

- PHP `openssl` 扩展
