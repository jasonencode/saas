# BlockChain

多链区块链集成层，支持 FISCO BCOS（联盟链）和 Chain33（公链/联盟链），提供智能合约部署、节点查询、密钥管理和交易签名。

## 架构

```
ChainType (Enum)
    ├── Fisco  → FiscoAdapter
    └── Chain33 → Chain33Adapter
                     └── Secp256k1KeyOps (共享 Trait)
```

## 目录结构

```
BlockChain/
├── Abi/
│   └── AdiEncoder.php          # Solidity ABI 编码器
├── Adapters/
│   ├── Traits/
│   │   └── Secp256k1KeyOps.php # secp256k1 密钥操作（共享）
│   ├── Chain33Adapter.php      # Chain33 适配器
│   └── FiscoAdapter.php        # FISCO BCOS 适配器
├── Rpc/
│   └── RpcClient.php           # JSON-RPC 2.0 客户端
└── Rlp/
    └── RlpEncoder.php          # RLP 编码器（Ethereum 格式）
```

## 核心类

| 类 | 职责 |
|----|------|
| `AbiEncoder` | Solidity ABI 编码，支持 uint/int/address/bool/bytes/string/array/tuple |
| `RlpEncoder` | RLP 编码，用于 FISCO 交易签名 |
| `RpcClient` | 通用 JSON-RPC 客户端，支持重试、SSL、请求 ID 追踪 |
| `Chain33Adapter` | Chain33 RPC 调用：区块高度、节点列表、合约部署、地址生成 |
| `FiscoAdapter` | FISCO BCOS RPC 调用：支持 Group 维度、EIP-1559 交易签名 |

## 使用方式

通过 `ChainType` 枚举获取适配器：

```php
use App\Enums\BlockChain\ChainType;

$adapter = ChainType::Fisco->getAdapter();

$blockNumber = $adapter->getBlockNumber();
$txHash = $adapter->deployContract($params);
```

## 密钥管理

`Secp256k1KeyOps` trait 提供密钥操作：

```php
$privateKey = $adapter->generatePrivateKey();
$publicKey = $adapter->getPublicKeyFromPrivateKey($privateKey);
$evmAddress = $adapter->evmAddressFromPublicKey($publicKey);
```

## 配置

`config/custom.php`：

```php
'block_chain' => [
    'public_key' => env('BLOCK_CHAIN_PUBLIC_KEY'),  # RSA 公钥，用于加密存储私钥
]
```

## 依赖

- `elliptic/php-elliptic` — secp256k1 曲线运算
- `kornrunner/keccak` — Keccak/SHA3 哈希
- `tuupola/base58` — Base58 编码
- PHP `gmp` 扩展（Chain33 Base58 编码需要）
