<?php

namespace App\Filament\Actions\BlockChain;

use App\Contracts\NetworkAdapterInterface;
use App\Enums\BlockChain\ChainType;
use App\Models\BlockChain\Network;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use RuntimeException;
use Throwable;

class GetPeersAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'getPeers';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('获取节点列表');
        $this->icon(Heroicon::OutlinedServerStack);
        $this->modalWidth('7xl');
        $this->modalSubmitAction(false);
        $this->visible(fn (Network $record): bool => userCan(self::getDefaultName(), $record));

        $this->schema(function (Network $record) {
            $rpcUrl = $record->rpc_url;

            if (blank($rpcUrl)) {
                $this->modalHeading('获取节点列表');

                return [
                    TextInput::make('_error')
                        ->label('')
                        ->default('该网络未配置 RPC 地址')
                        ->disabled()
                        ->columnSpanFull(),
                ];
            }

            /** @var ChainType $chainType */
            $chainType = $record->type;
            $adapterClass = $chainType->getAdapter();

            /** @var NetworkAdapterInterface $adapter */
            $adapter = app($adapterClass);

            try {
                $peers = $adapter->getPeers($rpcUrl, $record->getSslOptions(), $record->getGroupId());
                $peers = $peers['peers'] ?? $peers;

                if (empty($peers)) {
                    $this->modalHeading('获取节点列表');

                    return [
                        TextInput::make('_error')
                            ->label('')
                            ->default('未获取到节点信息')
                            ->disabled()
                            ->columnSpanFull(),
                    ];
                }

                $this->modalHeading(sprintf('节点列表（共 %d 个）', count($peers)));

                $peerFields = $this->buildPeerFields($peers);

                return [
                    Grid::make(1)
                        ->schema([
                            Repeater::make('peers')
                                ->label(sprintf('共 %d 个节点（%s）', count($peers), $chainType->getLabel()))
                                ->schema($peerFields)
                                ->default($peers)
                                ->disabled()
                                ->columns(3)
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false),
                        ]),
                ];
            } catch (RuntimeException $e) {
                $this->modalHeading('获取节点列表');

                return [
                    TextInput::make('_error')
                        ->label('')
                        ->default('获取节点列表失败：'.$e->getMessage())
                        ->disabled()
                        ->columnSpanFull(),
                ];
            } catch (Throwable $e) {
                $this->modalHeading('获取节点列表');

                return [
                    TextInput::make('_error')
                        ->label('')
                        ->default('获取节点列表异常：'.$e->getMessage())
                        ->disabled()
                        ->columnSpanFull(),
                ];
            }
        });
    }

    /**
     * 根据第一条 peer 数据的键名动态生成字段定义
     */
    private function buildPeerFields(array $peers): array
    {
        if (empty($peers)) {
            return [];
        }

        $first = (array) $peers[0];
        $fields = [];

        foreach (array_keys($first) as $key) {
            $label = match ($key) {
                'endPoint' => 'IP:端口',
                'p2pNodeID' => '节点 ID',
                default => $key,
            };

            $fields[] = TextInput::make($key)
                ->label($label)
                ->disabled();
        }

        return $fields;
    }
}
