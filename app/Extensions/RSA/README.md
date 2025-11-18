# RSA 工具使用说明

该工具提供 RSA 加解密、签名与验签，并支持密钥分块处理。

## 快速开始

```php
use App\Extensions\RSA\RSA;

// 1) 生成密钥对
$pair = RSA::generateKeyPair(2048);
$private = $pair['privateKey'];
$public  = $pair['publicKey'];

// 2) 创建实例（也可从文件加载）
$rsa = new RSA($private, $public);
// $rsa = RSA::fromKeyFiles('/path/private.pem', '/path/public.pem', 'passphrase');

// 3) 加密/解密
$cipher = $rsa->encrypt('hello'); // base64
$plain  = $rsa->decrypt($cipher);

// 4) 签名/验签
$sig = $rsa->sign('data'); // base64
$ok  = $rsa->verify('data', $sig); // bool
```

### 私钥加密 / 公钥解密（用于认证，不用于保密）

```php
use App\Extensions\RSA\RSA;

$rsa = new RSA($privatePem, $publicPem);

$cipher = $rsa->privateEncrypt('原文'); // base64，任何持有公钥者都可还原
$plain  = $rsa->publicDecrypt($cipher);
```

## 注意事项
- 依赖 PHP 的 `openssl` 扩展。
- 长文本加解密自动分块，采用 PKCS1 填充。
- 推荐使用 2048+ 位密钥。
- “私钥加密/公钥解密”不具备保密性，如需保密请使用“公钥加密/私钥解密”。
