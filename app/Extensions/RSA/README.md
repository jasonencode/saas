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

// 3) 公钥加密 / 私钥解密（保密通信）
$cipher = $rsa->encrypt('hello'); // base64
$plain  = $rsa->decrypt($cipher);

// 4) 私钥签名 / 公钥验签
$sig = $rsa->sign('data'); // base64
$ok  = $rsa->verify('data', $sig); // bool

// 5) 清理敏感数据
$rsa->destroy();
```

## API 速览

| 方法                                        | 说明             | 默认填充   |
|-------------------------------------------|----------------|--------|
| `encrypt(string, ?int)`                   | 公钥加密，返回 Base64 | OAEP   |
| `decrypt(string, ?int)`                   | 私钥解密，输入 Base64 | OAEP   |
| `sign(string, ?int)`                      | 私钥签名，返回 Base64 | SHA256 |
| `verify(string, string, ?int)`            | 公钥验签，返回 bool   | SHA256 |
| `destroy()`                               | 清理内存中的密钥数据     | -      |
| `generateKeyPair(int, ?string)`           | 静态方法，生成密钥对     | -      |
| `fromKeyFiles(?string, ?string, ?string)` | 静态方法，从文件加载密钥   | -      |

## 注意事项

- 依赖 PHP 的 `openssl` 扩展。
- 长文本加解密自动分块，采用 OAEP 填充（默认）。
- 推荐使用 2048+ 位密钥。
- 使用完毕后调用 `destroy()` 清理内存中的密钥数据。