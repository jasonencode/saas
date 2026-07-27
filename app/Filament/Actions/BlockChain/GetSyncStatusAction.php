<?php

namespace App\Filament\Actions\BlockChain;

use App\Contracts\NetworkAdapterInterface;
use App\Enums\BlockChain\ChainType;
use App\Models\BlockChain\Network;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use RuntimeException;
use Throwable;

class GetSyncStatusAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'getSyncStatus';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('获取同步状态');
        $this->icon(Heroicon::OutlinedArrowPath);
        $this->visible(fn (Network $record): bool => userCan(self::getDefaultName(), $record));
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
                $status = $adapter->getSyncStatus($rpcUrl, $record->getSslOptions(), $record->getGroupId());

                $blockNumber = $status['blockNumber'] ?? 'N/A';
                $knownHighest = $status['knownHighestNumber'] ?? $status['knownHighestBlockNumber'] ?? 'N/A';

                $this->successNotificationTitle(sprintf(
                    '同步状态 — 当前高度: %s, 最高高度: %s（%s）',
                    $blockNumber,
                    $knownHighest,
                    $chainType->getLabel()
                ));
                $this->success();
            } catch (RuntimeException $e) {
                $this->failureNotificationTitle(sprintf(
                    '获取同步状态失败：%s',
                    $e->getMessage()
                ));
                $this->failure();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('获取同步状态异常：'.$e->getMessage());
                $this->failure();
            }
        });
    }
}
