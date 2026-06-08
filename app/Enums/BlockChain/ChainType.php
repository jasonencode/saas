<?php

namespace App\Enums\BlockChain;

use App\Extensions\BlockChain\Adapters\Chain33Adapter;
use App\Extensions\BlockChain\Adapters\FiscoAdapter;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ChainType: string implements HasColor, HasLabel
{
    case Fisco = 'fisco';

    case Chain33 = 'chain33';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fisco => '飞梭 (FISCO)',
            self::Chain33 => '复杂美 (Chain33)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Chain33 => 'info',
            self::Fisco => 'success',
        };
    }

    public function getAdapter(): string
    {
        return match ($this) {
            self::Fisco => FiscoAdapter::class,
            self::Chain33 => Chain33Adapter::class,
        };
    }

    /**
     * @return array<string, array{label: string, type: string, placeholder?: string, help?: string, columnSpan: int}>
     */
    public function configFields(): array
    {
        $prefix = match ($this) {
            self::Fisco => 'fisco',
            self::Chain33 => 'chain33',
        };

        $fields = match ($this) {
            self::Fisco => [
                'group_id' => [
                    'label' => '群组 ID',
                    'type' => 'text',
                    'placeholder' => 'group0',
                    'help' => 'FISCO BCOS 节点群组 ID，默认 group0',
                    'columnSpan' => 1,
                ],
                'ca_cert' => [
                    'label' => 'CA 证书（PEM）',
                    'type' => 'textarea',
                    'placeholder' => "-----BEGIN CERTIFICATE-----\n...",
                    'help' => 'RPC 节点 SDK 中的 ca.crt 文件内容',
                    'columnSpan' => 1,
                ],
                'client_cert' => [
                    'label' => '客户端证书（PEM）',
                    'type' => 'textarea',
                    'placeholder' => "-----BEGIN CERTIFICATE-----\n...",
                    'help' => 'RPC 节点 SDK 中的 sdk.crt 文件内容',
                    'columnSpan' => 1,
                ],
                'client_key' => [
                    'label' => '客户端私钥（PEM）',
                    'type' => 'textarea',
                    'placeholder' => "-----BEGIN PRIVATE KEY-----\n...",
                    'help' => 'RPC 节点 SDK 中的 sdk.key 文件内容',
                    'columnSpan' => 1,
                ],
            ],
            self::Chain33 => [
                'is_parachain' => [
                    'label' => '是否平行链',
                    'type' => 'toggle',
                    'help' => '主链节点关闭，平行链节点开启后需要填写平行链名称和代扣地址',
                    'columnSpan' => 2,
                ],
                'para_name' => [
                    'label' => '平行链名称',
                    'type' => 'text',
                    'placeholder' => 'user.p.xxx.',
                    'help' => '格式：user.p.xxx.',
                    'columnSpan' => 1,
                ],
                'para_pay_addr' => [
                    'label' => '平行链代扣地址',
                    'type' => 'text',
                    'placeholder' => '1A1zP1e...',
                    'help' => '平行链交易手续费代扣钱包地址',
                    'columnSpan' => 1,
                ],
            ],
        };

        $prefixed = [];

        foreach ($fields as $key => $def) {
            $prefixed[$prefix.'.'.$key] = $def;
        }

        return $prefixed;
    }
}
