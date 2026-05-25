<?php

namespace App\Extensions\BlockChain\Adapters;

use App\Extensions\BlockChain\Rpc\RpcClient;

class EthAdapter extends AbstractEvmAdapterInterface
{
    public function getBlockNumber(string $rpcUrl, array $sslOptions = [], ?string $groupId = null): int
    {
        $rpc = new RpcClient($rpcUrl, 30);

        return (int) hexdec($rpc->send('eth_blockNumber'));
    }
}
