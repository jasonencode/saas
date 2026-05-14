<?php

namespace App\Extensions\BlockChain\Adapters;

class FiscoAdapter extends AbstractEvmAdapterInterface
{
    /**
     * FISCO BCOS 地址通过 SHA3-256 派生（非 Keccak-256）
     * 覆盖以使用 FISCO 特定的地址派生方式
     */
    public function getAddressFromPublicKey(string $publicKey): string
    {
        return $this->fiscoAddressFromPublicKey($publicKey);
    }

    /* deployContract() 继承自 AbstractEvmAdapterInterface —
     * FISCO BCOS 使用 EVM 兼容的 JSON-RPC（eth_sendRawTransaction、
     * eth_getTransactionReceipt）和相同的 ECDSA secp256k1 签名，
     * 交易哈希使用 Keccak-256。仅地址派生不同（地址使用 SHA3-256，
     * 由 fiscoAddressFromPublicKey 处理）。
     */
}
