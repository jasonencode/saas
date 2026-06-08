<?php

namespace App\Filament\Actions\BlockChain;

use App\Contracts\NetworkAdapterInterface;
use App\Enums\BlockChain\ChainType;
use App\Models\BlockChain\Network;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use RuntimeException;
use Throwable;

class GetBlockNumberAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'getBlockNumber';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('获取区块高度');
        $this->icon(Heroicon::OutlinedArrowPathRoundedSquare);
        $this->action(function (Network $record) {
            $rpcUrl = $record->rpc_url;

            if (blank($rpcUrl)) {
                $this->failureNotificationTitle('该网络未配置 RPC 地址');
                $this->failure();

                return;
            }

            /** @var ChainType $chainType */
            $chainType = $record->type;
            $adapterClass = $chainType->getAdapter();

            /** @var NetworkAdapterInterface $adapter */
            $adapter = app($adapterClass);

            try {
                $blockNumber = $adapter->getBlockNumber($rpcUrl, $record->getSslOptions(), $record->getGroupId());

                $this->successNotificationTitle(sprintf(
                    '当前区块高度为 %d（%s）',
                    $blockNumber,
                    $chainType->getLabel()
                ));
                $this->success();
            } catch (RuntimeException $e) {
                $this->failureNotificationTitle(sprintf(
                    '获取区块高度失败：%s',
                    $e->getMessage()
                ));
                $this->failure();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('获取区块高度异常：'.$e->getMessage());
                $this->failure();
            }
        });
    }
}
