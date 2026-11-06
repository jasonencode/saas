<?php

namespace App\Services\Mall\Seckill;

use App\Models\Campaign\SeckillSession;
use App\Models\Mall\Sku;
use App\Models\User\User;
use RuntimeException;

/**
 * 秒杀价格解析
 *
 * 秒杀价优先于会员折扣：命中有效场次时直接返回秒杀价。
 */
class SeckillPriceResolver
{
    /**
     * 解析秒杀价
     *
     * @throws RuntimeException 场次与 SKU 不匹配、场次不可购买
     */
    public function priceFor(User $user, SeckillSession $session, Sku $sku, int $qty = 1): string
    {
        if ((int) $session->sku_id !== (int) $sku->getKey()) {
            throw new RuntimeException('秒杀商品与下单商品不匹配');
        }

        if ($qty < 1) {
            throw new RuntimeException('购买数量必须大于 0');
        }

        if (!$session->isPurchasable()) {
            throw new RuntimeException('秒杀不在可购买状态');
        }

        return number_format((float) $session->seckill_price, 2, '.', '');
    }
}
