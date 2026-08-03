<?php

namespace App\Enums\System;

use App\Filament\Tenant\Clusters;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * 租户可用模块枚举
 *
 * 对应租户面板下各 Cluster，用于按租户开启/关闭模块。
 * 存储于 Tenant::config 的 modules 字段（数组），由 Tenant::modules() 取出。
 */
enum AvailableModule: string implements HasColor, HasLabel
{
    case Mall = 'mall';

    case Content = 'content';

    case Campaign = 'campaign';

    case Finance = 'finance';

    case BlockChain = 'block_chain';

    case User = 'user';

    case Foundation = 'foundation';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mall => '商城',
            self::Content => '内容',
            self::Campaign => '活动',
            self::Finance => '财务',
            self::BlockChain => '区块链',
            self::User => '用户',
            self::Foundation => '基础设施',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Mall => 'success',
            self::Content => 'info',
            self::Campaign => 'warning',
            self::Finance => 'primary',
            self::BlockChain => 'gray',
            self::User => 'primary',
            self::Foundation => 'gray',
        };
    }

    public function getClusterClass(): string
    {
        return match ($this) {
            self::Mall => Clusters\Mall\MallCluster::class,
            self::Content => Clusters\Content\ContentCluster::class,
            self::Campaign => Clusters\Campaign\CampaignCluster::class,
            self::Finance => Clusters\Finance\FinanceCluster::class,
            self::BlockChain => Clusters\BlockChain\BlockChainCluster::class,
            self::User => Clusters\User\UserCluster::class,
            self::Foundation => Clusters\Foundation\FoundationCluster::class,
        };
    }
}
