<?php

namespace App\Exceptions\Mall;

use RuntimeException;

/**
 * 商品库存不足
 *
 * 下单扣减库存与付款扣减库存共用该异常，支付链路据此回滚并触发原路退款。
 */
class InsufficientStockException extends RuntimeException {}
